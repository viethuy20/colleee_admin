@if(isset($note_list))
    @foreach ($note_list as $note_no=>$note)
        <div class="form-group stock_note" id="noteLayout{{ $note_no }}">
            <div class="form-inline">
                <div class="form-group">
                    <label for="noteId{{ $note_no }}">メモ{{ $note_no }}</label>
                    <div class="form-group">
                        <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeNote(this)">削除</a><br>
                    </div>
                </div>
                <br>
            </div>
            {{ Tag::formTextarea('note['.$note_no.']', old('programs.note',$note?? ''), ['class' => 'form-control','rows' => 3, 'id' => 'note'.$note_no ]) }}
        </div>
    @endforeach
@endif

@if(isset($note_no) && !isset($note_list))

<div class="form-group stock_note" id="noteLayout{{ $note_no }}">
    <div class="form-inline">
        <div class="form-group">
            <label for="noteId{{ $note_no }}">メモ{{ $note_no }}</label>
            <div class="form-group">
                <a class="btn btn-danger btn-small removenode"  hrerf="javascript:void(0);" onclick="removeNote(this)">削除</a><br>
            </div>
        </div>
        <br>
    </div>
    {{ Tag::formTextarea('note['.$note_no.']', old('programs.note',$program['note']?? ''), ['class' => 'form-control','rows' => 3, 'id' => 'note'.$note_no ]) }}
</div>
@endif

