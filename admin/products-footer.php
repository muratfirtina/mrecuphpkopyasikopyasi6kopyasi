
<?php
// Footer kısmı için minimal JavaScript
$pageJS = '';

// CKEditor ve external JS
$pageExtraHead = '
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script src="js/products.js"></script>
<style>
.ck-editor__editable_inline {
    min-height: 200px;
}
.preview-image {
    border: 2px solid #ddd;
}
</style>
';

// Footer include
include '../includes/admin_footer.php';
?>
