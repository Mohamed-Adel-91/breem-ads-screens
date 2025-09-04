<div class="col-md-3">
    <div class="form-group">
        <label for="name">Feature Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ request()->input('name', $filters['name'] ?? '') }}" placeholder="Search Features">
    </div>
</div>
