<?php
/**
 * JavaScript Fixes for Products.php
 */
$pageJS = '
// CKEditor için basit konfigürasyon
if (typeof ClassicEditor !== "undefined") {
    ClassicEditor
        .create(document.querySelector("#add_description"), {
            toolbar: ["heading", "|", "bold", "italic", "link", "bulletedList", "numberedList", "|", "outdent", "indent", "|", "imageUpload", "blockQuote", "insertTable", "mediaEmbed", "|", "undo", "redo"],
            image: {
                toolbar: ["imageTextAlternative", "imageStyle:full", "imageStyle:side"]
            }
        })
        .catch(error => {
            console.error("CKEditor yüklenemedi:", error);
        });
}

function viewProduct(productId) {
    // Ürün detay sayfasına yönlendir
    window.open("product-detail.php?id=" + productId, "_blank");
}

function editProduct(productId) {
    // Loading göster
    const modal = new bootstrap.Modal(document.getElementById("editProductModal"));
    
    // AJAX ile ürün bilgilerini getir
    fetch("ajax/get-product-details.php?id=" + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                // Form alanlarını doldur
                document.getElementById("edit_product_id").value = product.id;
                document.getElementById("edit_name").value = product.name || "";
                document.getElementById("edit_slug").value = product.slug || "";
                document.getElementById("edit_sku").value = product.sku || "";
                document.getElementById("edit_short_description").value = product.short_description || "";
                document.getElementById("edit_description").value = product.description || "";
                document.getElementById("edit_price").value = product.price || "";
                document.getElementById("edit_sale_price").value = product.sale_price || "";
                document.getElementById("edit_stock_quantity").value = product.stock_quantity || 0;
                document.getElementById("edit_weight").value = product.weight || "";
                document.getElementById("edit_dimensions").value = product.dimensions || "";
                document.getElementById("edit_sort_order").value = product.sort_order || 0;
                document.getElementById("edit_meta_title").value = product.meta_title || "";
                document.getElementById("edit_meta_description").value = product.meta_description || "";
                
                // Checkbox lar
                document.getElementById("edit_is_active").checked = product.is_active == 1;
                document.getElementById("edit_featured").checked = product.featured == 1;
                
                // Kategori seçeneklerini doldur
                const categorySelect = document.getElementById("edit_category_id");
                categorySelect.innerHTML = "<option value=\"\">Kategori Seçin</option>";
                data.categories.forEach(category => {
                    const option = document.createElement("option");
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id == product.category_id;
                    categorySelect.appendChild(option);
                });
                
                // Marka seçeneklerini doldur
                const brandSelect = document.getElementById("edit_brand_id");
                brandSelect.innerHTML = "<option value=\"\">Marka Seçin</option>";
                data.brands.forEach(brand => {
                    const option = document.createElement("option");
                    option.value = brand.id;
                    option.textContent = brand.name;
                    option.selected = brand.id == product.brand_id;
                    brandSelect.appendChild(option);
                });
                
                // Modal ı aç
                modal.show();
            } else {
                alert("Ürün bilgileri alınamadı: " + data.message);
            }
        })
        .catch(error => {
            console.error("Ürün bilgileri alınamadı:", error);
            alert("Bir hata oluştu.");
        });
}

// Edit form submit
document.addEventListener("DOMContentLoaded", function() {
    // URL de edit parametresi var mı kontrol et
    const urlParams = new URLSearchParams(window.location.search);
    const editProductId = urlParams.get("edit");
    
    if (editProductId) {
        // Edit modal ını aç
        setTimeout(() => {
            editProduct(editProductId);
        }, 500);
        
        // URL den edit parametresini temizle
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    const editForm = document.getElementById("editProductForm");
    if (editForm) {
        editForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const submitBtn = editForm.querySelector("button[type=\"submit\"]");
            const originalText = submitBtn.innerHTML;
            
            // Loading göster
            submitBtn.innerHTML = "<i class=\"bi bi-spinner fa-spin me-1\"></i>Güncelleniyor...";
            submitBtn.disabled = true;
            
            fetch("ajax/update-product.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success message
                    const successAlert = document.createElement("div");
                    successAlert.className = "alert alert-success alert-dismissible fade show";
                    successAlert.innerHTML = "<i class=\"bi bi-check-circle me-2\"></i>" + data.message + "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
                    
                    // Insert after page title
                    const pageTitle = document.querySelector(".card-header");
                    pageTitle.parentNode.insertBefore(successAlert, pageTitle.nextSibling);
                    
                    // Modal ı kapat
                    bootstrap.Modal.getInstance(document.getElementById("editProductModal")).hide();
                    
                    // Sayfayı yenile (ürün listesini güncellemek için)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert("Hata: " + data.message);
                }
            })
            .catch(error => {
                console.error("Güncelleme hatası:", error);
                alert("Bir hata oluştu.");
            })
            .finally(() => {
                // Loading i kaldır
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Modal temizleme
document.getElementById("addProductModal").addEventListener("hidden.bs.modal", function () {
    this.querySelector("form").reset();
    // CKEditor içeriğini temizle
    if (window.addDescriptionEditor) {
        window.addDescriptionEditor.setData("");
    }
});

// Dosya önizleme
function previewImages(input) {
    const previewContainer = document.getElementById("image_preview");
    if (!previewContainer) return;
    
    previewContainer.innerHTML = "";
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.className = "preview-image";
                img.style.cssText = "max-width: 100px; max-height: 100px; object-fit: cover; margin: 5px; border-radius: 4px;";
                
                const wrapper = document.createElement("div");
                wrapper.className = "d-inline-block position-relative";
                wrapper.appendChild(img);
                
                if (index === 0) {
                    const badge = document.createElement("span");
                    badge.className = "badge bg-primary position-absolute top-0 start-0";
                    badge.textContent = "Ana";
                    badge.style.fontSize = "10px";
                    wrapper.appendChild(badge);
                }
                
                previewContainer.appendChild(wrapper);
            }
            reader.readAsDataURL(file);
        });
    }
}
';

echo $pageJS;
?>
