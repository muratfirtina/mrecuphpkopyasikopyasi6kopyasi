                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sale_price" class="form-label">İndirimli Fiyat</label>
                                        <input type="number" class="form-control" name="sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                                <input type="number" class="form-control" name="stock_quantity" value="0" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marka</label>
                                <select class="form-select" name="brand_id">
                                    <option value="">Marka Seçin</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Ağırlık (kg)</label>
                                        <input type="number" class="form-control" name="weight" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sıralama</label>
                                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dimensions" class="form-label">Boyutlar</label>
                                <input type="text" class="form-control" name="dimensions" 
                                       placeholder="Örn: 10x20x30 cm">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum Ayarları</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                                    <label class="form-check-label">Aktif</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured">
                                    <label class="form-check-label">Öne Çıkan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Bölümü -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">SEO Bilgileri</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" name="meta_title" maxlength="255">
                                        <div class="form-text">Arama motorları için sayfa başlığı</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" name="meta_description" rows="3" maxlength="160"></textarea>
                                        <div class="form-text">Arama motorları için sayfa açıklaması (160 karakter)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Ürün Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// JavaScript dosyası ayrı olarak yükleniyor - PHP'de JavaScript yok!
$pageJS = '';

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

include '../includes/admin_footer.php';
?>
