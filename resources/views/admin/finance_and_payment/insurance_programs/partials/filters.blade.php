<div class="col-md-3">
    <div class="form-group">
        <label for="value">Sentences name</label>
        <input type="text" class="form-control" id="value" name="value" value="{{ request()->input('value', $filters['value'] ?? '') }}" placeholder="Search Sentences">
    </div>
</div>
