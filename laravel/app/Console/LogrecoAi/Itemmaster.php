<?php
namespace App\Console\LogrecoAi;

use DB;
use App\Console\BaseCommand;
use Illuminate\Support\Facades\Storage;
use WrapPhp;

/**
 * Description of Itemmaster
 *
 * @author k_miyashita
 */
class Itemmaster extends BaseCommand {
    use \App\DBTrait;

    protected $tag = 'logrecoai:itemmaster';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logrecoai:itemmaster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'logrecoai:itemmaster';

    private static $EXCEPT_ITEMMASTER_COLUMN_COUNT = 24;
    private static $SEPARATOR_COLON = ':';
    private static $SEPARATOR_COMMA = ',';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->programs = [];
        $this->articles = [];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('start');
        $this->getPrograms();
        $this->getArticles();
        $this->outputTsv();
        $this->uploadS3();
        $this->info('success');
        return 0;
    }

    private function getPrograms() {
        $sql = <<<EOF
SELECT
  CONCAT("pg", p.id) AS item_id,
  CASE WHEN p.status = 0 AND p.start_at <= NOW() AND NOW() < p.stop_at THEN '1' ELSE '0' END AS is_valid,
  p.updated_at AS date,
  'program' AS category1,
  '' AS category2,
  '' AS category3,
  '' AS category4,
  '' AS category5,
  p.fee_condition AS column1,
  p.multi_course AS column2,
  poi.fee_type AS column3,
  SUBSTRING(p.title, 1, 250) AS column4,
  REPLACE(REPLACE(SUBSTRING(p.description, 1, 250), '\r', ''), '\n', '') AS column5,
  '' AS column6,
  '' AS column7,
  '' AS column8,
  '' AS column9,
  '' AS column10,
  '0' AS number1,
  '0' AS number2,
  '0' AS number3,
  '' AS tags1,
  SUBSTRING(
    REGEXP_REPLACE(
      REGEXP_REPLACE(
        REGEXP_REPLACE(
          REPLACE(
            REPLACE(
              REPLACE(
                REPLACE(
                  REPLACE(
                    REPLACE(
                      REPLACE(
                        REPLACE(p.title,
                        '(', ':'),
                      ')', ':'),
                    '（', ':'),
                  '）', ':'),
                '【', ':'),
              '】', ':'),
            '_', ':'),
          '-', ':'),
        ':+', ':'),
      '^:', ''),
    ':$', '')
  , 1, 250) AS tags2,
  '' AS tags3
FROM programs AS p
LEFT JOIN points poi ON p.id = poi.program_id AND poi.start_at <= NOW() AND NOW() < poi.stop_at
ORDER BY p.id DESC;
EOF;
        $tmp_programs = DB::select($sql);
        foreach ($tmp_programs as $tmp_program) {
            $this->programs[$tmp_program->item_id] = (array)$tmp_program;
        }

        $tmp_devices = $this->getDevices();
        foreach ($tmp_devices as $tmp_device) {
            if (array_key_exists($tmp_device->item_id, $this->programs)) {
                $this->programs[$tmp_device->item_id]['category2'] = $tmp_device->category2;
            }
        }
        $tmp_program_labels = $this->getProgramLabels('category3', self::$SEPARATOR_COMMA);
        foreach ($tmp_program_labels as $tmp_program_label) {
            if (array_key_exists($tmp_program_label->item_id, $this->programs)) {
                $this->programs[$tmp_program_label->item_id]['category3'] = $tmp_program_label->category3;
            }
        }
        $tmp_program_labels = $this->getProgramLabels('category4', self::$SEPARATOR_COMMA);
        foreach ($tmp_program_labels as $tmp_program_label) {
            if (array_key_exists($tmp_program_label->item_id, $this->programs)) {
                $this->programs[$tmp_program_label->item_id]['category4'] = $tmp_program_label->category4;
            }
        }
        $tmp_program_labels = $this->getProgramLabels('category5', self::$SEPARATOR_COMMA);
        foreach ($tmp_program_labels as $tmp_program_label) {
            if (array_key_exists($tmp_program_label->item_id, $this->programs)) {
                $this->programs[$tmp_program_label->item_id]['category5'] = $tmp_program_label->category5;
            }
        }
        $tmp_program_labels = $this->getProgramLabels('column6', self::$SEPARATOR_COMMA);
        foreach ($tmp_program_labels as $tmp_program_label) {
            if (array_key_exists($tmp_program_label->item_id, $this->programs)) {
                $this->programs[$tmp_program_label->item_id]['column6'] = $tmp_program_label->column6;
            }
        }
        $tmp_points = $this->getPoints();
        foreach ($tmp_points as $tmp_point) {
            if (array_key_exists($tmp_point->item_id, $this->programs)) {
                $this->programs[$tmp_point->item_id]['number1'] = $tmp_point->number1;
            }
        }
        $tmp_program_tags = $this->getProgramTags('tags1', self::$SEPARATOR_COLON);
        foreach ($tmp_program_tags as $tmp_program_tag) {
            if (array_key_exists($tmp_program_tag->item_id, $this->programs)) {
                $this->programs[$tmp_program_tag->item_id]['tags1'] = $tmp_program_tag->tags1;
            }
        }
        // $tmp_shop_categories = $this->getShopCategories(self::$SEPARATOR_COLON);
        // foreach ($tmp_shop_categories as $tmp_shop_category) {
        //     if (array_key_exists($tmp_shop_category->item_id, $this->programs)) {
        //         $this->programs[$tmp_shop_category->item_id]['tags2'] = $tmp_shop_category->tags2;
        //     }
        // }
        $tmp_program_labels = $this->getProgramLabels('tags3', self::$SEPARATOR_COLON);
        foreach ($tmp_program_labels as $tmp_program_label) {
            if (array_key_exists($tmp_program_label->item_id, $this->programs)) {
                $this->programs[$tmp_program_label->item_id]['tags3'] = $tmp_program_label->tags3;
            }
        }

        // $this->info(str_replace("\n", "\n", var_export($this->programs, 1))); // debug
    }

    private function getDevices() {
        $map_device = config('map.device');
        $sql = <<<EOF
SELECT
  CONCAT("pg", p.id) AS item_id,
  p.devices AS category2
FROM programs p
ORDER BY p.id DESC;
EOF;
        $res = DB::select($sql);

        foreach ($res as $k => $v) {
            $devices = $this->int2Array($v->category2);
            $devices_val = [];
            foreach ($devices as $device) {
                $devices_val[] = $map_device[$device];
            }
            $res[$k]->category2 = implode(' ', $devices_val);
        }

        return $res;
    }

//     private function getShopCategories($sep = ',') {
//         $map_shop_category = config('map.shop_category');
//         $sql = <<<EOF
// SELECT
//   CONCAT("pg", p.id) AS item_id,
//   p.shop_categories AS tags2
// FROM programs p
// ORDER BY p.id DESC;
// EOF;
//         $res = DB::select($sql);

//         foreach ($res as $k => $v) {
//             $shop_categories = $this->int2Array($v->tags2);
//             $shop_categories_val = [];
//             foreach ($shop_categories as $shop_category) {
//                 $shop_categories_val[] = $map_shop_category[$shop_category];
//             }
//             $res[$k]->tags2 = implode($sep, $shop_categories_val);
//         }

//         return $res;
//     }

    private function getPoints() {
        $sql = <<<EOF
SELECT
  CONCAT('pg', t1.id) AS item_id,
  IFNULL(t1.point, '0') AS number1
FROM (
  SELECT
    p.id id,
    SUM(poi.point) point
  FROM programs p
  LEFT JOIN courses c ON p.id = c.program_id
  LEFT JOIN points poi ON p.id = poi.program_id AND c.id = poi.course_id AND poi.start_at <= NOW() AND NOW() < poi.stop_at AND poi.fee_type = 1
  WHERE p.status = 0 AND p.start_at <= NOW() AND NOW() < p.stop_at AND p.multi_course = 1 AND c.status = 0
  GROUP BY p.id
  UNION ALL
  SELECT
    p.id id,
    SUM(poi.point) point
  FROM programs p
  LEFT JOIN points poi ON p.id = poi.program_id AND poi.start_at <= NOW() AND NOW() < poi.stop_at AND poi.fee_type = 1
  WHERE p.status = 0 AND p.start_at <= NOW() AND NOW() < p.stop_at AND p.multi_course = 0
  GROUP BY p.id
) t1;
EOF;

        $res = DB::select($sql);
        return $res;
    }

    private function getProgramLabels($want, $sep = ',') {
        $cond_type = '';
        $cond_label = '';

        switch ($want) {
            case 'category3':
                $cond_type = ' l.type = 1 AND ';
                $cond_label = ' l.label_id = 0 AND ';
                $max_length = '61';
                break;
            case 'category4':
                $cond_type = ' l.type = 2 AND ';
                $cond_label = ' l.label_id = 0 AND ';
                $max_length = '61';
                break;
            case 'category5':
                $cond_type = ' l.type = 3 AND ';
                $cond_label = ' l.label_id = 0 AND ';
                $max_length = '61';
                break;
            case 'column6':
                $cond_type = ' l.type = 4 AND ';
                $cond_label = ' l.label_id = 0 AND ';
                $max_length = '250';
                break;
            case 'tags3':
                $cond_type = '';
                $cond_label = ' l.label_id <> 0 AND ';
                $max_length = '61';
                break;
        }

        $sql = <<<EOF
SELECT
  CONCAT("pg", p.id) AS item_id,
  CASE WHEN CHAR_LENGTH(GROUP_CONCAT(l.name SEPARATOR '{$sep}')) > {$max_length} THEN
    SUBSTRING_INDEX(GROUP_CONCAT(l.name SEPARATOR '{$sep}'), ',', COUNT(*)-2)
  ELSE
    GROUP_CONCAT(l.name SEPARATOR '{$sep}')
  END AS {$want}
FROM programs p LEFT JOIN program_labels pl ON p.id = pl.program_id LEFT JOIN labels l ON pl.label_id = l.id
WHERE {$cond_type} {$cond_label} pl.deleted_at IS NULL
GROUP BY p.id
ORDER BY p.id DESC;
EOF;

        $res = DB::select($sql);
        return $res;
    }

    private function getProgramTags($want, $sep = ',') {
        $res = [];
        $sql = <<<EOF
SELECT
  CONCAT("pg", p.id) AS item_id,
  GROUP_CONCAT(t.name SEPARATOR '{$sep}') AS {$want}
FROM programs p LEFT JOIN program_tags pt ON p.id = pt.program_id LEFT JOIN tags t ON pt.tag_id = t.id
WHERE pt.deleted_at IS NULL
GROUP BY p.id
ORDER BY p.id DESC;
EOF;

        $res = DB::select($sql);
        return $res;
    }

    private function getArticles() {
        $sql = <<<EOF
SELECT
    CONCAT('wp', wp_posts.ID) AS item_id,
    (CASE WHEN wp_posts.post_status = 'publish' THEN 1 ELSE 0 END) AS is_valid,
    wp_posts.post_date AS date,
    'wordpress' AS category1,
    'PC iOS Android' AS category2,
    SUBSTRING(MAX(IF(wp_postmeta_primary_category.meta_key = '_yoast_wpseo_primary_category', wp_terms_primary_cats.name, '')), 1, 64) AS category3,
    SUBSTRING(MAX(IF(wp_postmeta_primary_category.meta_key = '_yoast_wpseo_primary_category', wp_terms_primary_cats.name, '')), 1, 64) AS category4,
    '' AS category5,
    '' AS column1,
    '' AS column2,
    '' AS column3,
    SUBSTRING(wp_posts.post_title, 1, 250) AS column4,
    SUBSTRING(REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(wp_posts.post_content, '<[^>]+>', ''), '[\r\n\t]', ' '), ' +', ' '), '&nbsp;', ''), 1, 250) AS column5,
    '' AS column6,
    '' AS column7,
    '' AS column8,
    '' AS column9,
    '' AS column10,
    '0' AS number1,
    '0' AS number2,
    '0' AS number3,
    IFNULL(GROUP_CONCAT(DISTINCT wp_terms_tags.name SEPARATOR ':'), '') AS tags1,
    IFNULL(GROUP_CONCAT(DISTINCT wp_terms_tags.name SEPARATOR ':'), '') AS tags2,
    IFNULL(GROUP_CONCAT(DISTINCT wp_terms_cats.name SEPARATOR ':'), '') AS tags3
FROM wp_posts
LEFT JOIN wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id
LEFT JOIN wp_term_taxonomy AS wp_term_taxonomy_cats ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy_cats.term_taxonomy_id AND wp_term_taxonomy_cats.taxonomy = 'category'
LEFT JOIN wp_terms AS wp_terms_cats ON wp_term_taxonomy_cats.term_id = wp_terms_cats.term_id
LEFT JOIN wp_term_taxonomy AS wp_term_taxonomy_tags ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy_tags.term_taxonomy_id AND wp_term_taxonomy_tags.taxonomy = 'post_tag'
LEFT JOIN wp_terms AS wp_terms_tags ON wp_term_taxonomy_tags.term_id = wp_terms_tags.term_id
LEFT JOIN wp_postmeta AS wp_postmeta_primary_category ON wp_posts.ID = wp_postmeta_primary_category.post_id AND wp_postmeta_primary_category.meta_key = '_yoast_wpseo_primary_category'
LEFT JOIN wp_postmeta AS wp_postmeta_article_period_redirect ON wp_posts.ID = wp_postmeta_article_period_redirect.post_id AND wp_postmeta_article_period_redirect.meta_key = 'article_period_redirect'
LEFT JOIN wp_terms AS wp_terms_primary_cats ON wp_postmeta_primary_category.meta_value = wp_terms_primary_cats.term_id
WHERE wp_posts.post_type = 'post' AND wp_posts.post_status = 'publish'
    AND wp_postmeta_article_period_redirect.meta_value <> '0'
GROUP BY wp_posts.ID
ORDER BY wp_posts.ID DESC;
EOF;

        $tmp_articles = DB::connection('mysql2')->select($sql);
        foreach ($tmp_articles as $tmp_article) {
            $this->articles[$tmp_article->item_id] = (array)$tmp_article;
        }

        // $this->info(str_replace("\n", "\n", var_export($this->articles, 1))); // debug
    }

    private function outputTsv() {
        foreach ($this->programs as $key => $row) {
            foreach ($row as $cellKey => $cell) {
                $this->programs[$key][$cellKey] = str_replace("\t", ' ', $cell);
            }
        }
        foreach ($this->articles as $key => $row) {
            foreach ($row as $cellKey => $cell) {
                $this->articles[$key][$cellKey] = str_replace("\t", ' ', $cell);
            }
        }
        $dir_path = config('path.logrecoai');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, 0755);
        }
        $file_path = $dir_path . DIRECTORY_SEPARATOR . 'itemmaster.tsv';
        $file = fopen($file_path, 'w');
        fwrite($file, implode("\t", ['item_id', 'is_valid', 'date', 'category1', 'category2', 'category3', 'category4', 'category5', 'column1', 'column2', 'column3', 'column4', 'column5', 'column6', 'column7', 'column8', 'column9', 'column10', 'number1', 'number2', 'number3', 'tags1', 'tags2', 'tags3']) . "\n");
        foreach ($this->programs as $program) {
            if (WrapPhp::count($program) === self::$EXCEPT_ITEMMASTER_COLUMN_COUNT) {
                fwrite($file, implode("\t", $program) . "\n");
            }
        }
        foreach ($this->articles as $article) {
            if (WrapPhp::count($article) === self::$EXCEPT_ITEMMASTER_COLUMN_COUNT) {
                fwrite($file, implode("\t", $article) . "\n");
            }
        }
        fclose($file);
    }

    private function uploadS3() {
        $local_file_path = config('path.logrecoai') . DIRECTORY_SEPARATOR . 'itemmaster.tsv';
        Storage::disk('s3logrecoai')->put('itemmaster.tsv', file_get_contents($local_file_path));
    }

}
