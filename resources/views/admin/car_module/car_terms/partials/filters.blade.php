<div class="col-md-3">
    <div class="form-group">
        <label for="term_name">اسم الفئة</label>
        <input type="text" class="form-control" id="term_name" name="term_name" value="{{ request()->input('term_name', $filters['term_name'] ?? '') }}" placeholder="ابحث عن فئة">
    </div>
</div>
