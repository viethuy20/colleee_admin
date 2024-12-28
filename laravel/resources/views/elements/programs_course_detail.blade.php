<div class="form-group" id="courseLayout{{ $course_no }}">
    <div class="form-inline">
        <div class="form-group">
            <label for="affCourseId{{ $course_no }}">連携コースID</label>
            {{ Tag::formText('course[' .  $course_no . '][aff_course_id]', old('course'.  $course_no .'.aff_course_id', $course['aff_course_id'] ?? ''), ['class' => 'form-control', 'id' => 'AffCourseId' .  $course_no]) }}<br />
        </div>
        &nbsp;&nbsp;
        <div class="form-group">
            <label for="courseName{{ $course_no }}">コース名</label>
            {{ Tag::formText('course[' .  $course_no . '][course_name]', old('course.course_name', $course['course_name'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'CourseName'. $course_no]) }}<br />
        </div>
        &nbsp;&nbsp;
        <div class="form-group">
            <label for="priority{{ $course_no }}">表示順</label>
            {{ Tag::formNumber('course[' .  $course_no . '][priority]', old('course.priority', $course['priority'] ?? '1'), ['class' => 'form-control', 'id' => 'Priority'. $course_no]) }}<br />
        </div>
        <div class="form-group">
            <a class="btn btn-danger btn-small"  hrerf="javascript:void(0);" onclick="removeCourse(this)">削除</a><br>
        </div>
    </div>
</div>
