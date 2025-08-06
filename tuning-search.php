<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚗 Araç Tuning Veritabanı - Mr ECU</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .search-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .search-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .results-section {
            margin-top: 30px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .results-count {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .view-toggle {
            display: flex;
            gap: 5px;
        }

        .view-toggle .btn {
            padding: 8px 16px;
            font-size: 12px;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .result-card {
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, #3498db, #2ecc71);
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: #3498db;
        }

        .card-header {
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .card-subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .power-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }

        .power-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .power-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .power-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .power-gain {
            color: #27ae60;
            font-size: 0.9rem;
            margin-top: 3px;
        }

        .fuel-badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .fuel-badge.benzin {
            background: #e74c3c;
        }

        .fuel-badge.petrol {
            background: #e74c3c;
        }

        .fuel-badge.diesel {
            background: #2c3e50;
        }

        .ecu-info {
            margin-top: 10px;
            padding: 8px;
            background: #ecf0f1;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #5d6d7e;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .results-table th {
            background: #3498db;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .results-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e1e8ed;
            font-size: 0.9rem;
        }

        .results-table tr:hover {
            background: #f8f9fa;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            display: block;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .power-section {
                grid-template-columns: 1fr;
            }
            
            .search-buttons {
                justify-content: center;
            }
            
            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Araç Tuning Veritabanı</h1>
            <p>Chip tuning performans verileri ve ECU bilgileri</p>
        </div>

        <div class="content">
            <!-- Arama Bölümü -->
            <div class="search-section">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">🔍 Araç Arama</h2>
                
                <div class="search-grid">
                    <div class="form-group">
                        <label for="brand">Marka</label>
                        <select id="brand">
                            <option value="">Tüm Markalar</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <select id="model" disabled>
                            <option value="">Önce marka seçin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_type">Yakıt Tipi</label>
                        <select id="fuel_type">
                            <option value="">Tümü</option>
                            <option value="Benzin">Benzin</option>
                            <option value="Diesel">Dizel</option>
                            <option value="Hybrid">Hibrit</option>
                            <option value="Electric">Elektrik</option>
                            <option value="LPG">LPG</option>
                            <option value="CNG">CNG</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_power">Min. Güç (HP)</label>
                        <input type="number" id="min_power" placeholder="Örn: 100">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_power">Max. Güç (HP)</label>
                        <input type="number" id="max_power" placeholder="Örn: 300">
                    </div>
                    
                    <div class="form-group">
                        <label for="search_text">Genel Arama</label>
                        <input type="text" id="search_text" placeholder="Motor, ECU, model ara...">
                    </div>
                </div>
                
                <div class="search-buttons">
                    <button class="btn btn-primary" onclick="searchTuning()">🔍 Ara</button>
                    <button class="btn btn-secondary" onclick="clearSearch()">🗑️ Temizle</button>
                    <button class="btn btn-success" onclick="loadPopular()">🔥 Popüler</button>
                    <button class="btn btn-info" onclick="loadHighestGains()">⚡ En Yüksek Artış</button>
                </div>
            </div>

            <!-- Sonuçlar Bölümü -->
            <div class="results-section">
                <div class="results-header">
                    <div class="results-count" id="results-count">Sonuç bekleniyor...</div>
                    <div class="view-toggle">
                        <button class="btn btn-primary" id="card-view" onclick="setView('card')">📄 Kart</button>
                        <button class="btn btn-secondary" id="table-view" onclick="setView('table')">📊 Tablo</button>
                    </div>
                </div>
                
                <div id="results-container">
                    <div class="loading">Arama yapın veya popüler sonuçları görüntüleyin</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentView = 'card';
        let currentResults = [];

        // Sayfa yüklendiğinde markaları yükle
        document.addEventListener('DOMContentLoaded', function() {
            loadBrands();
            loadPopular(); // Başlangıçta popüler sonuçları göster
        });

        // Markaları yükle
        async function loadBrands() {
            try {
                const response = await fetch('tuning-api.php?action=brands');
                const data = await response.json();
                
                if (data.success) {
                    const brandSelect = document.getElementById('brand');
                    brandSelect.innerHTML = '<option value="">Tüm Markalar</option>';
                    
                    data.data.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand.id;
                        option.textContent = `${brand.name} (${brand.model_count} model)`;
                        brandSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Marka yükleme hatası:', error);
            }
        }

        // Marka değiştiğinde modelleri yükle
        document.getElementById('brand').addEventListener('change', async function() {
            const brandId = this.value;
            const modelSelect = document.getElementById('model');
            
            if (!brandId) {
                modelSelect.innerHTML = '<option value="">Önce marka seçin</option>';
                modelSelect.disabled = true;
                return;
            }
            
            try {
                const response = await fetch(`tuning-api.php?action=models&brand_id=${brandId}`);
                const data = await response.json();
                
                if (data.success) {
                    modelSelect.innerHTML = '<option value="">Tüm Modeller</option>';
                    
                    data.data.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.name;
                        option.textContent = `${model.name} (${model.series_count} seri)`;
                        modelSelect.appendChild(option);
                    });
                    
                    modelSelect.disabled = false;
                }
            } catch (error) {
                console.error('Model yükleme hatası:', error);
            }
        });

        // Arama fonksiyonu
        async function searchTuning() {
            const filters = {
                brand: document.getElementById('brand').selectedOptions[0]?.textContent.split(' (')[0] || '',
                model: document.getElementById('model').value,
                fuel_type: document.getElementById('fuel_type').value,
                min_power: document.getElementById('min_power').value,
                max_power: document.getElementById('max_power').value,
                q: document.getElementById('search_text').value,
                limit: 100
            };

            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) params.append(key, filters[key]);
            });

            try {
                showLoading();
                const response = await fetch(`tuning-api.php?action=search&${params}`);
                const data = await response.json();
                
                if (data.success) {
                    currentResults = data.data;
                    displayResults(currentResults);
                    updateResultsCount(currentResults.length);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                showError('Arama sırasında hata oluştu: ' + error.message);
            }
        }

        // Popüler sonuçları yükle
        async function loadPopular() {
            try {
                showLoading();
                const response = await fetch('tuning-api.php?action=popular&limit=20');
                const data = await response.json();
                
                if (data.success) {
                    // Popüler motorları search formatına çevir
                    const searchResults = data.data.map(engine => ({
                        brand_name: engine.brand_name,
                        model_name: engine.model_name,
                        year_range: engine.year_range,
                        engine_name: engine.name,
                        fuel_type: engine.fuel_type,
                        stage_name: 'Popular',
                        fullname: `${engine.brand_name} ${engine.model_name} ${engine.name}`,
                        original_power: Math.round(engine.max_tuning_power / 1.2),
                        tuning_power: engine.max_tuning_power,
                        difference_power: Math.round(engine.avg_power_gain),
                        original_torque: 0,
                        tuning_torque: 0,
                        difference_torque: 0,
                        ecu: `${engine.stage_count} farklı stage mevcut`
                    }));
                    
                    currentResults = searchResults;
                    displayResults(currentResults);
                    updateResultsCount(currentResults.length, 'Popüler Motor');
                }
            } catch (error) {
                showError('Popüler veriler yüklenirken hata oluştu: ' + error.message);
            }
        }

        // En yüksek artışları yükle
        async function loadHighestGains() {
            try {
                showLoading();
                const response = await fetch('tuning-api.php?action=highest_gains&limit=20');
                const data = await response.json();
                
                if (data.success) {
                    currentResults = data.data;
                    displayResults(currentResults);
                    updateResultsCount(currentResults.length, 'En Yüksek Güç Artışı');
                }
            } catch (error) {
                showError('En yüksek artış verileri yüklenirken hata oluştu: ' + error.message);
            }
        }

        // Sonuçları göster
        function displayResults(results) {
            const container = document.getElementById('results-container');
            
            if (results.length === 0) {
                container.innerHTML = '<div class="no-results">🔍 Sonuç bulunamadı. Farklı filtreler deneyin.</div>';
                return;
            }

            if (currentView === 'card') {
                displayCardsView(results);
            } else {
                displayTableView(results);
            }
        }

        // Kart görünümü
        function displayCardsView(results) {
            const container = document.getElementById('results-container');
            
            const cardsHtml = results.map(result => `
                <div class="result-card">
                    <div class="card-header">
                        <div class="card-title">${result.fullname}</div>
                        <div class="card-subtitle">${result.brand_name} ${result.model_name} - ${result.year_range}</div>
                    </div>
                    
                    <div class="power-section">
                        <div class="power-box">
                            <div class="power-label">Orijinal Güç</div>
                            <div class="power-value">${result.original_power} HP</div>
                        </div>
                        <div class="power-box">
                            <div class="power-label">Tuning Güç</div>
                            <div class="power-value">${result.tuning_power} HP</div>
                            <div class="power-gain">+${result.difference_power} HP</div>
                        </div>
                    </div>
                    
                    ${result.original_torque > 0 ? `
                    <div class="power-section">
                        <div class="power-box">
                            <div class="power-label">Orijinal Tork</div>
                            <div class="power-value">${result.original_torque} Nm</div>
                        </div>
                        <div class="power-box">
                            <div class="power-label">Tuning Tork</div>
                            <div class="power-value">${result.tuning_torque} Nm</div>
                            <div class="power-gain">+${result.difference_torque} Nm</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <span class="fuel-badge ${result.fuel_type.toLowerCase()}">${result.fuel_type}</span>
                    
                    ${result.ecu ? `<div class="ecu-info"><strong>ECU:</strong> ${result.ecu}</div>` : ''}
                </div>
            `).join('');
            
            container.innerHTML = `<div class="results-grid">${cardsHtml}</div>`;
        }

        // Tablo görünümü
        function displayTableView(results) {
            const container = document.getElementById('results-container');
            
            const tableHtml = `
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Araç</th>
                            <th>Motor</th>
                            <th>Yakıt</th>
                            <th>Orijinal</th>
                            <th>Tuning</th>
                            <th>Artış</th>
                            <th>ECU</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${results.map(result => `
                            <tr>
                                <td><strong>${result.brand_name} ${result.model_name}</strong><br><small>${result.year_range}</small></td>
                                <td>${result.engine_name}</td>
                                <td><span class="fuel-badge ${result.fuel_type.toLowerCase()}">${result.fuel_type}</span></td>
                                <td>${result.original_power} HP<br><small>${result.original_torque} Nm</small></td>
                                <td>${result.tuning_power} HP<br><small>${result.tuning_torque} Nm</small></td>
                                <td style="color: #27ae60; font-weight: bold;">+${result.difference_power} HP<br><small>+${result.difference_torque} Nm</small></td>
                                <td><small>${result.ecu || 'Belirtilmemiş'}</small></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            
            container.innerHTML = tableHtml;
        }

        // Görünüm değiştir
        function setView(view) {
            currentView = view;
            
            document.getElementById('card-view').className = view === 'card' ? 'btn btn-primary' : 'btn btn-secondary';
            document.getElementById('table-view').className = view === 'table' ? 'btn btn-primary' : 'btn btn-secondary';
            
            if (currentResults.length > 0) {
                displayResults(currentResults);
            }
        }

        // Sonuç sayısını güncelle
        function updateResultsCount(count, type = 'Sonuç') {
            document.getElementById('results-count').textContent = `${count} ${type} Bulundu`;
        }

        // Yükleniyor göster
        function showLoading() {
            document.getElementById('results-container').innerHTML = '<div class="loading">🔄 Yükleniyor...</div>';
        }

        // Hata göster
        function showError(message) {
            document.getElementById('results-container').innerHTML = `<div class="no-results">❌ ${message}</div>`;
        }

        // Arama temizle
        function clearSearch() {
            document.getElementById('brand').value = '';
            document.getElementById('model').value = '';
            document.getElementById('model').disabled = true;
            document.getElementById('fuel_type').value = '';
            document.getElementById('min_power').value = '';
            document.getElementById('max_power').value = '';
            document.getElementById('search_text').value = '';
            
            // Sonuçları temizle
            currentResults = [];
            document.getElementById('results-container').innerHTML = '<div class="loading">Arama yapın veya popüler sonuçları görüntüleyin</div>';
            document.getElementById('results-count').textContent = 'Sonuç bekleniyor...';
        }

        // Enter tuşu ile arama
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTuning();
            }
        });
    </script>
</body>
</html>