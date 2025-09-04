<div class="col-md-3">
    <div class="form-group">
        <label for="name">Model Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ request()->input('name', $filters['name'] ?? '') }}" placeholder="Search Models">
    </div>
</div>
