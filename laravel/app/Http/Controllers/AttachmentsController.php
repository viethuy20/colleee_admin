<?php
namespace App\Http\Controllers;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

use App\Attachment;
use App\Program;
use App\SpProgram;
use App\Content;

/**
 * Description of AttachmentsController
 *
 * @author t_moriizumi
 */
class AttachmentsController extends Controller
{
    /**
     * 親情報を取得.
     * @param string $parent_type 親種類
     * @param int $parent_id 親ID
     */
    private function getParentData(string $parent_type, int $parent_id) {
        if ($parent_type == 'program') {
            return Program::find($parent_id);
        }
        if ($parent_type == 'sp_program') {
            return SpProgram::find($parent_id);
        }
        if($parent_type == 'content') {
            return Content::find($parent_id);
        }
        return null;
    }

    /**
     * 画像ページ.
     * @param Request $request リクエスト
     */
    public function images(Request $request)
    {
        //
        $type = $request->filled('type') ? $request->input('type') : 'default';

        $parent_type = null;
        $parent_id = null;
        if ($request->exists('img_ids')) {
            // 画像IDを取得
            $img_ids = $request->input('img_ids');
        } else {
            // 親の種類が存在しない場合
            if (!$request->filled('parent_type') || !$request->filled('parent_id')) {
                return abort(500, 'Internal Server Error');
            }

            $parent_type = $request->input('parent_type');
            $parent_id = $request->input('parent_id');

            $parent_data = $this->getParentData($parent_type, $parent_id);
            // 親が存在しない場合
            if (!isset($parent_data->id)) {
                return abort(500, 'Internal Server Error');
            }
            $img_ids = isset($parent_data->img_ids) ? $parent_data->img_ids : null;
        }

        // 添付ファイルリスト取得
        if (isset($img_ids)) {
            $img_list = Attachment::ofList(explode(',', $img_ids))
                    ->get();
        } else {
            $img_list = collect();
        }

        $returnHTML = view('attachments.images', ['img_list' => $img_list, 'type' => $type,
            'parent_type' => $parent_type, 'parent_id' => $parent_id])->render();

        return response()->json(array('success' => true, 'html'=> $returnHTML));
    }

    /**
     * アップロード.
     * @param Request $request リクエスト
     */
    public function upload(Request $request)
    {
        //
        $validator = Validator::make(
            $request->all(),
            ['file' => 'required|file',],
            [],
            ['file' => 'ファイル',]
        );

        // 書式検証
        if ($validator->fails()) {
            \Log::info($validator->errors()->first());
            return abort(500, 'Internal Server Error');
        }

        // 初期ステータス
        $status = ($request->filled('parent_type') && $request->filled('parent_id')) ? 0 : 1;
        //
        $attachment = Attachment::getDefault();
        // 画像保存実行
        $res = $attachment->saveImg($request->file('file'), $status);

        // 失敗
        if (!$res) {
            return abort(500, 'Internal Server Error');
        }

        if ($attachment->status == 0) {
            // 親が存在する場合
            $parent_data = $this->getParentData($request->input('parent_type'), $request->input('parent_id'));
            // 親が存在しない場合
            if (!isset($parent_data->id)) {
                return abort(500, 'Internal Server Error');
            }
            $img_ids = (isset($parent_data->img_ids) ? $parent_data->img_ids.',' : '') . $attachment->id;
            $parent_data->img_ids = $img_ids;
            $res = DB::transaction(function () use ($parent_data) {
                // 保存
                $parent_data->save();
                return true;
            });

            if (!$res) {
                return abort(500, 'Internal Server Error');
            }
        } else {
            // 画像以外を取得
            $datas = $request->except('file');
            $img_ids = (isset($datas['img_ids']) ? $datas['img_ids'].',' : '') . $attachment->id;
        }

        return response()->json((object)['location' => $attachment->full_url, 'img_ids' => $img_ids], '200');
    }
}
