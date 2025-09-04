<div class="td-img-size-table">
    <img src="{{ $src }}" alt="Media Preview" class="img-thumbnail img-size-table view-full-image"
        data-src="{{ $dataSrc }}">
</div>
<!-- Simple Image Modal -->
<div id="simpleImageModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); z-index:1000;">
    <div style="display:flex; justify-content:center; align-items:center; height:100%;">
        <div
            style="background-color:white; padding:20px; border-radius:5px; position:relative; max-width:90%; max-height:90%;">
            <img id="simpleModalImage" src="" style="max-width:100%; max-height:80vh;">
            <div style="text-align:right; margin-top:10px;">
                <button onclick="closeSimpleModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>
</div>
@push('custom-js-scripts')
    <script>
        function showSimpleModal(imageSrc) {
            document.getElementById('simpleModalImage').src = imageSrc;
            document.getElementById('simpleImageModal').style.display = 'block';
        }
        function closeSimpleModal() {
            document.getElementById('simpleImageModal').style.display = 'none';
        }
        // Add click handlers to all images
        document.addEventListener('DOMContentLoaded', function() {
            var images = document.querySelectorAll('.img-size-table');
            images.forEach(function(img) {
                img.addEventListener('click', function() {
                    showSimpleModal(this.src);
                });
            });
        });
    </script>
@endpush
