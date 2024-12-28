
    @if($label->child_list->isNotEmpty())
    <a href="#" class = "showList">+ 子ラベルを表示</a>
    <ul class="childList" id="ParentLabel{{$label->id}}">
    @foreach($label->child_list as $child_label)
    <li><a href="" class="AjaxTag" id = "Label{{$child_label->id}}">{{$child_label->name}}</a></li>
    <?php $check_child_label = new \App\Label(); ?>
    @if($label->child_list->isNotEmpty())
        @include('elements.label_show', ['label' => $child_label])
    @endif
    @endforeach
    </ul>
    @endif




   