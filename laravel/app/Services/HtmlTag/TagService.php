<?php
namespace App\Services\HtmlTag;

use Illuminate\Support\HtmlString;

class TagService
{

    private function parseOption($option)
    {
        if(empty($option)) {
            return '';
        }
        $optionStr = '';
        foreach($option as $key => $value) {
            $optionStr .= $key.'="'.$value.'" ';
        }
        return $optionStr;
    }

    private function render($html){
        return new HtmlString($html);
    }

    public function style($url)
    {
        return $this->render('<link rel="stylesheet" href="'.$url.'">');
    }

    public function script($url,$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<script src="'.$url.'" $option></script>');
    }

    public function meta($name,$content)
    {
        return $this->render('<meta name="'.$name.'" content="'.$content.'">');
    }

    public function title($title)
    {
        return $this->render("<title>$title</title>");
    }

    public function link($url,$text,$option = [],$target = null,$confirm = false)
    {
        $option = $this->parseOption($option);
        $target = is_null($target) ? '' : 'target="'.$target.'"';
        $confirm = $confirm ? "onclick='return confirm(\"Are you sure?\")'" : '';
        return $this->render('<a href="'.$url.'" '.$option.' '.$target.' '.$confirm.'>'.$text.'</a>');
    }

    public function image($url,$title='',$option = [])
    {
        $option = $this->parseOption($option);
        $html =  '<img src="'.$url.'"';
        if($title) {
            $html .= ' alt="'.$title.'" ';
        }
        $html .= ' '.$option.'>';
        return $this->render($html);
    }

    public function formButton($text,$option = [])
    {
        if(!isset($option['type'])){
            $option['type'] = 'button';
        }
        $option = $this->parseOption($option);
        return $this->render("<button $option>$text</button>");
    }

    public function formOpen($data)
    {
        if(!empty($data['route'])) {
            $data['url'] = route($data['route']);
        }
        if(!empty($data['files'])) {
            $data['enctype'] = 'multipart/form-data';
        }
        $url = $data['url'];
        $method = $data['method']??'POST';
        unset($data['url']);
        unset($data['method']);
        unset($data['file']);

        $option = $this->parseOption($data);
        return $this->render('<form action="'.$url.'" method="'.$method.'" '.$option.'>');
    }

    public function formClose()
    {
        return $this->render("</form>");
    }

    public function formHidden($name,$value,$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="hidden" name="'.$name.'" value="'.$value.'" '.$option.'>');
    }

    public function formText($name,$value = '',$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="text" name="'.$name.'" value="'.$value.'" '.$option.'>');
    }

    public function formPassword($name,$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="password" name="'.$name.'" '.$option.'>');
    }

    public function formSubmit($text,$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="submit" value="'.$text.'" '.$option.'>');
    }

    public function formSelect($name,$options,$selected = null,$option = [],$optionsAttributes = [])
    {
        $disabled = '';
        if(isset($option['disabled']) && $option['disabled'] == true) {
            $disabled = 'disabled';
        }
        unset($option['disabled']);
        $option = $this->parseOption($option);

        $html = '<select name="'.$name.'" '.$option.' '.$disabled.'>';
        foreach($options as $key => $value) {
            $attributes = $this->parseOption($optionsAttributes[$key]??[]);

            $selectedStr = ($selected == $key) ? 'selected' : '';
            $html .= '<option value="'.$key.'" '.$attributes.' '.$selectedStr.'>'.$value.'</option>';
        }
        $html .= '</select>';
        return $this->render($html);
    }

    public function formTextarea($name,$value = '',$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<textarea name="'.$name.'" '.$option.'>'.$value.'</textarea>');
    }

    public function formCheckbox($name,$value,$checked = false,$option = [])
    {
        $option = $this->parseOption($option);
        $checkedStr = $checked ? 'checked' : '';
        return $this->render('<input type="checkbox" name="'.$name.'" value="'.$value.'" '.$checkedStr.' '.$option.'>');
    }
    
    public function formRadio($name,$value,$checked = false,$option = [])
    {
        $option = $this->parseOption($option);
        $checkedStr = $checked ? 'checked' : '';
        return $this->render('<input type="radio" name="'.$name.'" value="'.$value.'" '.$checkedStr.' '.$option.'>');
    }

    public function formFile($name,$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="file" name="'.$name.'" '.$option.' enctype="multipart/form-data">');
    }
    
    public function formTime($name,$value = '',$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="time" name="'.$name.'" value="'.$value.'" '.$option.'>');
    }

    public function formNumber($name,$value = '',$option = [])
    {
        $option = $this->parseOption($option);
        return $this->render('<input type="number" name="'.$name.'" value="'.$value.'" '.$option.'>');
    }

}