<script type="text/javascript">
<!--
var IMG_IDS_ID = '#{{ $img_ids_id }}';

var imgDialogInfo = {
    img : null,
    img_url : null
};

var opt = {
    autoOpen: false,
    title: '画像',
    closeOnEscape: true,
    modal: true,
    minWidth: 940,
    minHeight: 640,
    //height: 'auto'
    close: function() {
        var n = $( ".ui-dialog" ).length;
        if (n > 1) {
            $('.ui-dialog').not(':last').remove();
        }
    }
};

var openImageDialog = function ($img_id, $url_id) {
    @if (isset($parent_type) && isset($parent_id))
    var getParams = '?parent_type={{ $parent_type }}&parent_id={{ $parent_id }}';
    @else
    var getParams = '?img_ids=' + $(IMG_IDS_ID).val();
    @endif
    
    imgDialogInfo.img = $('#' + $img_id);
    imgDialogInfo.img_url = $('#' + $url_id);
    var dialog = $('#ImageDialog').dialog(opt);
    // 画像URL処理
    $('.fileUrl').on('change', function(event) {
        var img = $('#' + $(this).attr('forImg'));
        // 読み込んだデータをimgに設定
        img.attr('src', $(this).val());
        // 表示
        img.show();
    });


    // dialog.attr('src', '{!! route('attachments.images') !!}' + getParams);
    $.ajax({
        type: "GET",
        url: '{!! route('attachments.images') !!}' + getParams,
        dataType: 'json',
        cache: false,
        success: function(html) {
            dialog.html(html.html);
            dialog.show();
            dialog.dialog('open');
        },
    });
   
};

var myImage = {
    oninsert : function(url, img_ids) {
        imgDialogInfo.img_url.val(url);
        $img = imgDialogInfo.img;
        $img.attr('src', url);
        $img.show();
        if (img_ids) {
            $(IMG_IDS_ID).val(img_ids);
        }
    },
    close : function() {
        var dialog = $('#ImageDialog').dialog(opt);
        var n = $( ".ui-dialog" ).length;
        if(n > 1) {
            $('.ui-dialog').not(':last').remove();
        }
        $('.ui-widget-overlay').hide();
        imgDialogInfo.img = null;
        imgDialogInfo.img_url = null;
    }
};

var imageFilePicker = function (callback, value, meta) {
    var imgIdsInput = $(IMG_IDS_ID);
    var getParams = '?type=tinymce';
    @if (isset($parent_type) && isset($parent_id))
    getParams = getParams + '&parent_type={{ $parent_type }}&parent_id={{ $parent_id }}';
    @else
    getParams = getParams + '&img_ids=' + $(IMG_IDS_ID).val();
    @endif
    
    tinymce.activeEditor.windowManager.open({
        title: 'Image Picker',
        url: '{!! route('attachments.images') !!}' + getParams,
        width: 650,
        height: 550,
        buttons: [{
            text: 'Insert',
            onclick: function () {
                //.. do some work
                tinymce.activeEditor.windowManager.close();
            }
        }, {
            text: 'Close',
            onclick: 'close'
        }],
    }, {
        oninsert: function (url, img_ids) {
            callback(url, {width: '100%', height: ''});
            if (img_ids) {
                imgIdsInput.val(img_ids);
            }
            console.log("derp");
        },
    });
};

// -->
</script>
            
<div src="" id="ImageDialog" style="min-width:940px;min-height:940px;display:none;"></div>