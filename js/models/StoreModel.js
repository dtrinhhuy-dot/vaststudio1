// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: MODEL LAYER
// File: js/models/StoreModel.js
// Responsible for data structures, business logic & persistence (LocalStorage)
// ==========================================================================

(function(window) {
    'use strict';

    const STORAGE_KEYS = {
        PRODUCTS: 'vast_products_v4',
        ORDERS: 'vast_orders_v1',
        COLLECTIONS: 'vast_collections_v1',
        CUSTOMERS: 'vast_customers_v1',
        COUPONS: 'vast_coupons_v1',
        SETTINGS: 'vast_settings_v1',
        CART: 'vast_cart_v1',
        WISHLIST: 'vast_wishlist_v1',
        REVIEWS: 'vast_reviews_v1',
        THEME: 'vast_theme_v1'
    };

    function createFashionPlaceholder(label, bg = '#18181b', fg = '#a1a1aa') {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="500" viewBox="0 0 400 500">
            <rect width="400" height="500" fill="${bg}"/>
            <circle cx="200" cy="180" r="60" fill="#27272a"/>
            <path d="M120 320 C120 250, 280 250, 280 320 L280 440 L120 440 Z" fill="#27272a"/>
            <text x="200" y="470" fill="${fg}" font-family="sans-serif" font-size="16" font-weight="600" text-anchor="middle">VAST STUDIO</text>
            <text x="200" y="380" fill="#f4f4f5" font-family="sans-serif" font-size="18" font-weight="700" text-anchor="middle">${label}</text>
        </svg>`;
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    const SEED_PRODUCTS = [
        {
            id: 'PRD-001',
            name: 'Áo Thun VAST Heavyweight Boxy Tee',
            sku: 'VAST-TSH-01',
            category: 'Áo thun',
            collection: 'Streetwear Core',
            price: 380000,
            originalPrice: 450000,
            stock: 45,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Đen Washed', 'Trắng Vintage', 'Xám Ti-tan'],
            image: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Heavyweight Boxy Tee', '#18181b'),
            description: 'Chất liệu 100% Cotton 280GSM dày dặn, form boxy đứng dáng, đường may trần đè kỹ thuật cao. Thêu logo VAST viền kim loại sau gáy.',
            status: 'active',
            salesCount: 142,
            createdAt: '2026-09-10'
        },
        {
            id: 'PRD-002',
            name: 'Áo Sơ Mi VAST Oversized Oxford',
            sku: 'VAST-SHR-02',
            category: 'Áo sơ mi',
            collection: 'Minimalist Essentials',
            price: 520000,
            originalPrice: 590000,
            stock: 28,
            sizes: ['M', 'L', 'XL'],
            colors: ['Xanh Pastel', 'Trắng Sữa', 'Kẻ Sọc Nhuyễn'],
            image: 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Oversized Oxford', '#27272a'),
            description: 'Vải Oxford dệt mật độ cao chống nhăn nhẹ, thiết kế drop-shoulder hiện đại, cúc xà cừ khắc chìm chữ VAST.',
            status: 'active',
            salesCount: 98,
            createdAt: '2026-09-15'
        },
        {
            id: 'PRD-003',
            name: 'Hoodie VAST Acid Wash Vintage Drop',
            sku: 'VAST-HD-03',
            category: 'Hoodie & Sweater',
            collection: 'Monochrome Season 2026',
            price: 690000,
            originalPrice: 790000,
            stock: 18,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Xám Bạc Acid', 'Đen Tuyển'],
            image: 'https://images.unsplash.com/photo-1516826957135-700dedea698c?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Acid Wash Hoodie', '#1c1917'),
            description: 'Nỉ bông lót cào cao cấp 400GSM siêu ấm. Xử lý wash hiệu ứng sờn rách nghệ thuật, mũ 2 lớp phồng đứng.',
            status: 'active',
            salesCount: 86,
            createdAt: '2026-09-20'
        },
        {
            id: 'PRD-004',
            name: 'Quần Parachute Cargo Pants VAST',
            sku: 'VAST-PNT-04',
            category: 'Quần',
            collection: 'Streetwear Core',
            price: 620000,
            originalPrice: 690000,
            stock: 32,
            sizes: ['M', 'L', 'XL'],
            colors: ['Đen Obsidian', 'Xanh Rêu Sage', 'Be Cát'],
            image: 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Cargo Pants', '#292524'),
            description: 'Vải dù chống nước nhẹ (water-repellent), khóa rút gấu đa năng chuyển đổi giữa ống suông và bo gấu thời thượng.',
            status: 'active',
            salesCount: 110,
            createdAt: '2026-09-22'
        },
        {
            id: 'PRD-005',
            name: 'Quần Wide-Leg Raw Denim VAST',
            sku: 'VAST-DNM-05',
            category: 'Quần',
            collection: 'Streetwear Core',
            price: 680000,
            originalPrice: 750000,
            stock: 12,
            sizes: ['29', '30', '31', '32'],
            colors: ['Xanh Indigo Chàm', 'Đen Tuyển'],
            image: 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Raw Denim Wide-Leg', '#1e293b'),
            description: 'Denim dệt thoi 13oz bền bỉ chuẩn phong cách Nhật Bản. Form ống suông rộng tôn dáng, tag da bò dập nhiệt.',
            status: 'active',
            salesCount: 64,
            createdAt: '2026-10-01'
        },
        {
            id: 'PRD-006',
            name: 'Áo Khoác Varsity Bomber Da & Dạ VAST',
            sku: 'VAST-JKT-06',
            category: 'Áo khoác',
            collection: 'Winter Outerwear Drop',
            price: 1250000,
            originalPrice: 1450000,
            stock: 6,
            sizes: ['M', 'L'],
            colors: ['Đen Phối Trắng', 'Xanh Lục Bảo'],
            image: 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Varsity Bomber', '#0f172a'),
            description: 'Thân áo dạ cừu tổng hợp giữ nhiệt, tay da PU lì chống nổ cao cấp. Thêu xù logo VAST 3D.',
            status: 'active',
            salesCount: 39,
            createdAt: '2026-10-05'
        },
        {
            id: 'PRD-007',
            name: 'Áo Len Dệt Kim Textured Knit Sweater',
            sku: 'VAST-SWT-07',
            category: 'Hoodie & Sweater',
            collection: 'Minimalist Essentials',
            price: 580000,
            originalPrice: 650000,
            stock: 3,
            sizes: ['M', 'L'],
            colors: ['Kem Vanilla', 'Nâu Mocha'],
            image: 'https://images.unsplash.com/photo-1525450824786-227cbef70703?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Textured Knit Sweater', '#44403c'),
            description: 'Sợi len dệt quả trám mềm mịn không ngứa. Form oversize trẻ trung cho mùa lạnh.',
            status: 'active',
            salesCount: 47,
            createdAt: '2026-10-12'
        },
        {
            id: 'PRD-008',
            name: 'Mũ Beanie VAST Metal Badge',
            sku: 'VAST-ACC-08',
            category: 'Phụ kiện',
            collection: 'Minimalist Essentials',
            price: 220000,
            originalPrice: 280000,
            stock: 55,
            sizes: ['Freesize'],
            colors: ['Đen', 'Xám Khói', 'Nâu'],
            image: 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Beanie Metal Badge', '#18181b'),
            description: 'Len gân co giãn 4 chiều ôm form đầu chuẩn. Đính tag kim loại mạ bạc chống gỉ tinh tế.',
            status: 'active',
            salesCount: 168,
            createdAt: '2026-10-15'
        },
        {
            id: 'PRD-009',
            name: 'Áo Thun Graphic "Cyber Acid" Oversized Tee',
            sku: 'VAST-TSH-09',
            category: 'Áo thun',
            collection: 'Streetwear Core',
            price: 420000,
            originalPrice: 490000,
            stock: 38,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Đen Khói Acid', 'Xám Bê Tông'],
            image: 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Cyber Acid Tee', '#18181b'),
            description: 'Họa tiết graphic in lụa thủ công hiệu ứng Cyber Acid độc quyền. Chất liệu cotton 260GSM thoáng mát, chống bai nhão cổ.',
            status: 'active',
            salesCount: 115,
            createdAt: '2026-10-18'
        },
        {
            id: 'PRD-010',
            name: 'Áo Polo Dệt Kim VAST Retro Knit Polo',
            sku: 'VAST-POL-10',
            category: 'Áo thun',
            collection: 'Minimalist Essentials',
            price: 460000,
            originalPrice: 540000,
            stock: 24,
            sizes: ['M', 'L', 'XL'],
            colors: ['Xanh Navy Vintage', 'Kem Yến Mạch'],
            image: 'https://images.unsplash.com/photo-1586363104862-3a5e2ab60d99?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Retro Knit Polo', '#1e293b'),
            description: 'Cổ polo mở không cúc phong cách relaxed vintage. Sợi len cotton dệt thoáng khí, mềm rũ tự nhiên chuẩn quý ông đường phố.',
            status: 'active',
            salesCount: 84,
            createdAt: '2026-10-20'
        },
        {
            id: 'PRD-011',
            name: 'Áo Thun Dài Tay Striped Long-Sleeve Boxy',
            sku: 'VAST-TSH-11',
            category: 'Áo thun',
            collection: 'Monochrome Season 2026',
            price: 410000,
            originalPrice: 480000,
            stock: 35,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Kẻ Sọc Đen Trắng', 'Kẻ Sọc Xám Khói'],
            image: 'https://images.unsplash.com/photo-1584030373081-f37b7bb4fa8e?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Striped Long-Sleeve', '#18181b'),
            description: 'Thiết kế sọc ngang monochrome đen trắng kinh điển, bo tay cotton spandex co giãn êm ái, form drop-shoulder cá tính.',
            status: 'active',
            salesCount: 92,
            createdAt: '2026-10-22'
        },
        {
            id: 'PRD-012',
            name: 'Áo Sơ Mi Flannel Kẻ Caro Grunge Street',
            sku: 'VAST-SHR-12',
            category: 'Áo sơ mi',
            collection: 'Streetwear Core',
            price: 550000,
            originalPrice: 640000,
            stock: 20,
            sizes: ['M', 'L', 'XL'],
            colors: ['Đỏ Rượu Caro', 'Xám Đen Grunge'],
            image: 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Flannel Grunge Shirt', '#3f1515'),
            description: 'Vải flannel chải lông mềm mại giữ ấm tốt, họa tiết kẻ caro phối màu retro grunge, vạt bầu dễ phối layer với áo thun trắng.',
            status: 'active',
            salesCount: 104,
            createdAt: '2026-10-25'
        },
        {
            id: 'PRD-013',
            name: 'Áo Sơ Mi Cổ Cuba Cuban Collar Linen Blend',
            sku: 'VAST-SHR-13',
            category: 'Áo sơ mi',
            collection: 'Minimalist Essentials',
            price: 490000,
            originalPrice: 560000,
            stock: 27,
            sizes: ['M', 'L', 'XL'],
            colors: ['Trắng Cát', 'Xanh Olive Nhạt'],
            image: 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Cuban Collar Shirt', '#e2e8f0'),
            description: 'Chất vải pha đũi Linen cao cấp mát lạnh, phom suông phóng khoáng mang lại cảm giác nhẹ nhàng, tinh tế cho những ngày hè.',
            status: 'active',
            salesCount: 76,
            createdAt: '2026-10-28'
        },
        {
            id: 'PRD-014',
            name: 'Áo Sơ Mi Denim Western Denim Overshirt',
            sku: 'VAST-SHR-14',
            category: 'Áo sơ mi',
            collection: 'Streetwear Core',
            price: 620000,
            originalPrice: 720000,
            stock: 19,
            sizes: ['M', 'L', 'XL'],
            colors: ['Xanh Khói Wash', 'Chàm Indigo Đậm'],
            image: 'https://images.unsplash.com/photo-1589310243389-96a5483213a8?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Western Denim Overshirt', '#1e3a8a'),
            description: 'Vải denim 9oz wash màu khói bụi bặm, túi nắp kép trước ngực và đường cúp vai Western chuẩn vintage nam tính.',
            status: 'active',
            salesCount: 65,
            createdAt: '2026-10-30'
        },
        {
            id: 'PRD-015',
            name: 'Áo Hoodie Zip Boxy Heavyweight Double-Zipper',
            sku: 'VAST-HD-15',
            category: 'Hoodie & Sweater',
            collection: 'Monochrome Season 2026',
            price: 720000,
            originalPrice: 850000,
            stock: 16,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Đen Midnight', 'Xám Tiêu Melange'],
            image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Double Zip Hoodie', '#111827'),
            description: 'Nỉ chân cua 380GSM form hộp đứng phom. Khóa kéo 2 chiều YKK kim loại mạ bạc tiện lợi biến tấu dáng mặc, túi kangaroo xẻ đôi.',
            status: 'active',
            salesCount: 128,
            createdAt: '2026-11-01'
        },
        {
            id: 'PRD-016',
            name: 'Áo Sweater Crewneck Thêu Nổi "VAST ARCHIVE"',
            sku: 'VAST-SWT-16',
            category: 'Hoodie & Sweater',
            collection: 'Minimalist Essentials',
            price: 590000,
            originalPrice: 680000,
            stock: 22,
            sizes: ['M', 'L', 'XL'],
            colors: ['Xanh Cổ Vịt', 'Xám Tro'],
            image: 'https://images.unsplash.com/photo-1578587018452-892bacefd3f2?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Crewneck Archive', '#134e4a'),
            description: 'Cổ tròn dày 3cm chống dão, logo VAST Archive thêu xù nổi bật trước ngực. Chất vải nỉ da cá thoáng khí mặc được cả 4 mùa.',
            status: 'active',
            salesCount: 95,
            createdAt: '2026-11-03'
        },
        {
            id: 'PRD-017',
            name: 'Áo Cardigan Len Dệt Thoi Oversized VAST',
            sku: 'VAST-SWT-17',
            category: 'Hoodie & Sweater',
            collection: 'Minimalist Essentials',
            price: 640000,
            originalPrice: 750000,
            stock: 14,
            sizes: ['M', 'L'],
            colors: ['Họa Tiết Đen Trắng', 'Nâu Đất Houndstooth'],
            image: 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Oversized Cardigan', '#27272a'),
            description: 'Len dệt phối màu tương phản, viền cổ chữ V khoét sâu cùng cúc áo sừng tự nhiên. Phong cách Parisian chic kết hợp Streetwear.',
            status: 'active',
            salesCount: 58,
            createdAt: '2026-11-05'
        },
        {
            id: 'PRD-018',
            name: 'Quần Baggy Khaki Chino Xếp Ly Đôi',
            sku: 'VAST-PNT-18',
            category: 'Quần',
            collection: 'Minimalist Essentials',
            price: 560000,
            originalPrice: 650000,
            stock: 30,
            sizes: ['29', '30', '31', '32'],
            colors: ['Be Khaki', 'Đen Tuyển', 'Nâu Olive'],
            image: 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Double Pleated Chino', '#78350f'),
            description: 'Chất vải khaki cotton đanh mịn co giãn nhẹ, chi tiết 2 ly xếp trước tạo độ phồng hông vừa vặn che khuyết điểm chân hoàn hảo.',
            status: 'active',
            salesCount: 88,
            createdAt: '2026-11-06'
        },
        {
            id: 'PRD-019',
            name: 'Quần Jean Rách Gối Grunge Distressed Denim',
            sku: 'VAST-DNM-19',
            category: 'Quần',
            collection: 'Streetwear Core',
            price: 710000,
            originalPrice: 820000,
            stock: 15,
            sizes: ['29', '30', '31', '32'],
            colors: ['Xanh Bạc Dirty Wash', 'Đen Tro Rách Gối'],
            image: 'https://images.unsplash.com/photo-1582552938357-32b906df40cb?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Distressed Denim', '#1e293b'),
            description: 'Xử lý mài rách thủ công kết hợp kỹ thuật wash acid độc đáo. Form baggy ống rộng phủ giày phong cách skate & Y2K.',
            status: 'active',
            salesCount: 79,
            createdAt: '2026-11-08'
        },
        {
            id: 'PRD-020',
            name: 'Quần Short Túi Hộp Cargo Shorts 6 Túi',
            sku: 'VAST-PNT-20',
            category: 'Quần',
            collection: 'Streetwear Core',
            price: 440000,
            originalPrice: 520000,
            stock: 36,
            sizes: ['M', 'L', 'XL'],
            colors: ['Đen Matte', 'Xám Xi Măng', 'Xanh Rêu'],
            image: 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Cargo Shorts', '#18181b'),
            description: 'Quần short dù gió chống nhăn, cạp chun có dây rút bên trong và 6 ngăn túi hộp tiện ích cho các chuyến đi dã ngoại hay dạo phố.',
            status: 'active',
            salesCount: 112,
            createdAt: '2026-11-09'
        },
        {
            id: 'PRD-021',
            name: 'Áo Khoác Biker Da PU Vintage Leather Jacket',
            sku: 'VAST-JKT-21',
            category: 'Áo khoác',
            collection: 'Monochrome Season 2026',
            price: 1350000,
            originalPrice: 1600000,
            stock: 8,
            sizes: ['M', 'L', 'XL'],
            colors: ['Đen Da Bóng Lì'],
            image: 'https://images.unsplash.com/photo-1520975916090-3105956dac38?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Biker Leather Jacket', '#09090b'),
            description: 'Da PU vi sợi cao cấp chống bong tróc, lót lụa satin mượt mát. Khóa chéo kim loại hầm hố chuẩn phong cách Rocker đường phố.',
            status: 'active',
            salesCount: 41,
            createdAt: '2026-11-10'
        },
        {
            id: 'PRD-022',
            name: 'Áo Khoác Gió Techwear Windbreaker 2 Lớp',
            sku: 'VAST-JKT-22',
            category: 'Áo khoác',
            collection: 'Streetwear Core',
            price: 780000,
            originalPrice: 890000,
            stock: 25,
            sizes: ['S', 'M', 'L', 'XL'],
            colors: ['Đen Phối Phản Quang', 'Xám Bạc'],
            image: 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Techwear Windbreaker', '#18181b'),
            description: 'Vải dù Gore-Tex chống thấm nước tuyệt đối, cản gió giữ ấm và có mũ trùm giấu cổ tiện lợi. Nhiều ngăn túi zip chống nước bí mật.',
            status: 'active',
            salesCount: 97,
            createdAt: '2026-11-11'
        },
        {
            id: 'PRD-023',
            name: 'Áo Khoác Phao Puffer Jacket Dáng Ngắn Boxy',
            sku: 'VAST-JKT-23',
            category: 'Áo khoác',
            collection: 'Winter Outerwear Drop',
            price: 1150000,
            originalPrice: 1350000,
            stock: 10,
            sizes: ['M', 'L'],
            colors: ['Đen Phồng', 'Trắng Băng Tuyết', 'Nâu Caramen'],
            image: 'https://images.unsplash.com/photo-1544441893-675973e31985?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Puffer Jacket Boxy', '#1f2937'),
            description: 'Bông vi sợi siêu nhẹ êm ái, giữ nhiệt cực đỉnh trong thời tiết đại hàn. Dáng crop boxy hiện đại tôn chiều cao khi phối cùng quần ống rộng.',
            status: 'active',
            salesCount: 52,
            createdAt: '2026-11-12'
        },
        {
            id: 'PRD-024',
            name: 'Áo Khoác Denim Lót Lông Cừu Sherpa Trucker',
            sku: 'VAST-JKT-24',
            category: 'Áo khoác',
            collection: 'Winter Outerwear Drop',
            price: 980000,
            originalPrice: 1150000,
            stock: 11,
            sizes: ['M', 'L', 'XL'],
            colors: ['Xanh Bạc Khói', 'Đen Washed'],
            image: 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Sherpa Denim Trucker', '#1e3a8a'),
            description: 'Vỏ ngoài denim dày dặn màu xanh bạc, lót thân trong bằng lông cừu Sherpa siêu ấm cúng. Thiết kế huyền thoại vượt thời gian.',
            status: 'active',
            salesCount: 63,
            createdAt: '2026-11-13'
        },
        {
            id: 'PRD-025',
            name: 'Túi Đeo Chéo Crossbody Mini Pouch VAST',
            sku: 'VAST-ACC-25',
            category: 'Phụ kiện',
            collection: 'Streetwear Core',
            price: 290000,
            originalPrice: 350000,
            stock: 48,
            sizes: ['Freesize'],
            colors: ['Đen Matte', 'Xanh Rêu Camo'],
            image: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Crossbody Pouch', '#18181b'),
            description: 'Chất vải Cordura 1000D chống mài mòn, khóa bấm nam châm Fidlock hiện đại. Đựng vừa điện thoại, ví tiền và chìa khóa nhỏ gọn.',
            status: 'active',
            salesCount: 145,
            createdAt: '2026-11-14'
        },
        {
            id: 'PRD-026',
            name: 'Túi Tote Vải Canvas Mộc VAST Studio Club',
            sku: 'VAST-ACC-26',
            category: 'Phụ kiện',
            collection: 'Minimalist Essentials',
            price: 190000,
            originalPrice: 250000,
            stock: 60,
            sizes: ['Freesize'],
            colors: ['Trắng Mộc', 'Đen Tuyển'],
            image: 'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Canvas Tote Bag', '#f4f4f5'),
            description: 'Vải canvas sợi bông thô tự nhiên dày dặn, có ngăn phụ kéo khóa bên trong. Đựng vừa laptop 15.6 inch và sách vở đi học, đi làm.',
            status: 'active',
            salesCount: 198,
            createdAt: '2026-11-15'
        },
        {
            id: 'PRD-027',
            name: 'Mũ Lưỡi Trai Nón Kết VAST Dad Hat Washed',
            sku: 'VAST-ACC-27',
            category: 'Phụ kiện',
            collection: 'Streetwear Core',
            price: 240000,
            originalPrice: 300000,
            stock: 50,
            sizes: ['Freesize'],
            colors: ['Đen Faded', 'Xanh Chàm Wash', 'Nâu Đất'],
            image: 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Washed Dad Hat', '#27272a'),
            description: 'Cotton chải wash sờn phong cách Dad Hat bụi bặm, khóa cài đồng vintage phía sau dễ dàng điều chỉnh kích cỡ đầu.',
            status: 'active',
            salesCount: 172,
            createdAt: '2026-11-16'
        },
        {
            id: 'PRD-028',
            name: 'Thắt Lưng Vải Dù Quân Đội Tactical Buckle Belt',
            sku: 'VAST-ACC-28',
            category: 'Phụ kiện',
            collection: 'Streetwear Core',
            price: 180000,
            originalPrice: 230000,
            stock: 42,
            sizes: ['Freesize'],
            colors: ['Đen', 'Xanh Lính', 'Cát Sa Mạc'],
            image: 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=500&auto=format&fit=crop&q=80',
            fallbackImage: createFashionPlaceholder('Tactical Belt', '#18181b'),
            description: 'Dây dù dệt mật độ cao chống tưa rách, khóa kim loại bấm mở nhanh 1 chạm công nghệ Tactical chuẩn phong cách chiến thuật.',
            status: 'active',
            salesCount: 133,
            createdAt: '2026-11-17'
        }
    ];

    const SEED_ORDERS = [
        {
            id: 'VAST-9825',
            customerName: 'Nguyễn Hoàng Minh',
            customerPhone: '0912 345 678',
            customerEmail: 'hoangminh.design@gmail.com',
            shippingAddress: 'Tầng 8, Tòa Keangnam Landmark 72, Phạm Hùng, Q. Nam Từ Liêm, Hà Nội',
            items: [
                { id: 'PRD-001', name: 'Áo Thun VAST Heavyweight Boxy Tee', size: 'L', color: 'Đen Washed', price: 380000, quantity: 2, image: SEED_PRODUCTS[0].image },
                { id: 'PRD-004', name: 'Quần Parachute Cargo Pants VAST', size: 'L', color: 'Đen Obsidian', price: 620000, quantity: 1, image: SEED_PRODUCTS[3].image }
            ],
            subtotal: 1380000,
            shippingFee: 0,
            discount: 100000,
            couponCode: 'VASTWELCOME',
            total: 1280000,
            paymentMethod: 'Chuyển khoản QR (Vietcombank)',
            paymentStatus: 'Đã thanh toán',
            orderStatus: 'pending',
            note: 'Giao giờ hành chính, gọi trước 15 phút giúp mình nhé.',
            createdAt: '2026-11-14 14:20'
        },
        {
            id: 'VAST-9824',
            customerName: 'Trần Thảo Ly',
            customerPhone: '0978 998 877',
            customerEmail: 'thaoly.tran@outlook.com',
            shippingAddress: 'Căn hộ 12B, Chung cư Masteri Thảo Điền, P. Thảo Điền, TP. Thủ Đức, TP.HCM',
            items: [
                { id: 'PRD-002', name: 'Áo Sơ Mi VAST Oversized Oxford', size: 'M', color: 'Trắng Sữa', price: 520000, quantity: 1, image: SEED_PRODUCTS[1].image },
                { id: 'PRD-008', name: 'Mũ Beanie VAST Metal Badge', size: 'Freesize', color: 'Đen', price: 220000, quantity: 1, image: SEED_PRODUCTS[7].image }
            ],
            subtotal: 740000,
            shippingFee: 0,
            discount: 0,
            couponCode: null,
            total: 740000,
            paymentMethod: 'Ví điện tử MoMo',
            paymentStatus: 'Đã thanh toán',
            orderStatus: 'processing',
            note: 'Đóng gói hộp quà VAST giúp mình nhé, mình tặng sinh nhật bạn.',
            createdAt: '2026-11-14 11:45'
        },
        {
            id: 'VAST-9823',
            customerName: 'Lê Tuấn Hưng',
            customerPhone: '0903 112 233',
            customerEmail: 'tuanhung.le@gmail.com',
            shippingAddress: '45/12 Nguyễn Văn Linh, P. Nam Dương, Q. Hải Châu, Đà Nẵng',
            items: [
                { id: 'PRD-006', name: 'Áo Khoác Varsity Bomber Da & Dạ VAST', size: 'L', color: 'Đen Phối Trắng', price: 1250000, quantity: 1, image: SEED_PRODUCTS[5].image }
            ],
            subtotal: 1250000,
            shippingFee: 0,
            discount: 187500,
            couponCode: 'VIPMEMBER',
            total: 1062500,
            paymentMethod: 'COD (Thanh toán khi nhận hàng)',
            paymentStatus: 'Chưa thanh toán',
            orderStatus: 'shipping',
            note: '',
            createdAt: '2026-11-13 18:10'
        },
        {
            id: 'VAST-9822',
            customerName: 'Phạm Đức Anh',
            customerPhone: '0934 556 789',
            customerEmail: 'ducanh.arch@gmail.com',
            shippingAddress: '15 Lê Duẩn, P. Bến Nghé, Quận 1, TP.HCM',
            items: [
                { id: 'PRD-003', name: 'Hoodie VAST Acid Wash Vintage Drop', size: 'XL', color: 'Xám Bạc Acid', price: 690000, quantity: 1, image: SEED_PRODUCTS[2].image },
                { id: 'PRD-005', name: 'Quần Wide-Leg Raw Denim VAST', size: '31', color: 'Xanh Indigo Chàm', price: 680000, quantity: 1, image: SEED_PRODUCTS[4].image }
            ],
            subtotal: 1370000,
            shippingFee: 0,
            discount: 50000,
            couponCode: 'SUMMER50K',
            total: 1320000,
            paymentMethod: 'Chuyển khoản QR (Vietcombank)',
            paymentStatus: 'Đã thanh toán',
            orderStatus: 'completed',
            note: 'Đã nhận đủ hàng, chất lượng vải cực kỳ đẹp.',
            createdAt: '2026-11-12 09:30'
        },
        {
            id: 'VAST-9821',
            customerName: 'Hoàng Kim Chi',
            customerPhone: '0981 223 344',
            customerEmail: 'kimchi.fashion@gmail.com',
            shippingAddress: 'Số 88 Hàng Bài, Q. Hoàn Kiếm, Hà Nội',
            items: [
                { id: 'PRD-007', name: 'Áo Len Dệt Kim Textured Knit Sweater', size: 'M', color: 'Kem Vanilla', price: 580000, quantity: 1, image: SEED_PRODUCTS[6].image }
            ],
            subtotal: 580000,
            shippingFee: 30000,
            discount: 0,
            couponCode: null,
            total: 610000,
            paymentMethod: 'COD (Thanh toán khi nhận hàng)',
            paymentStatus: 'Đã hoàn tiền',
            orderStatus: 'cancelled',
            note: 'Khách hàng đổi ý muốn chờ đợt restock màu Nâu Mocha.',
            createdAt: '2026-11-11 16:15'
        }
    ];

    const SEED_COLLECTIONS = [
        {
            id: 'COL-001',
            name: 'Monochrome Season 2026',
            slug: 'monochrome-2026',
            description: 'Tối giản với gam màu đen trắng và xám khói kinh điển, đề cao form dáng cấu trúc và chất liệu dệt bền vững.',
            productsCount: 14,
            banner: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
            featured: true,
            status: 'active'
        },
        {
            id: 'COL-002',
            name: 'Streetwear Core',
            slug: 'streetwear-core',
            description: 'Trái tim của VAST STUDIO: Heavyweight Tees, Parachute Pants, và Wide-leg Denim đậm chất đường phố đương đại.',
            productsCount: 22,
            banner: 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=800&auto=format&fit=crop&q=80',
            featured: true,
            status: 'active'
        },
        {
            id: 'COL-003',
            name: 'Minimalist Essentials',
            slug: 'minimalist-essentials',
            description: 'Những món đồ tinh tế không bao giờ lỗi mốt: Sơ mi Oxford drop-shoulder, quần tây ống đứng và áo knit cao cấp.',
            productsCount: 18,
            banner: 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=800&auto=format&fit=crop&q=80',
            featured: false,
            status: 'active'
        },
        {
            id: 'COL-004',
            name: 'Winter Outerwear Drop',
            slug: 'winter-outerwear',
            description: 'Bộ sưu tập áo khoác Bomber da dạ, Trench coat tối giản và Puffer jackets đón gió mùa.',
            productsCount: 9,
            banner: 'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80',
            featured: true,
            status: 'active'
        }
    ];

    const SEED_CUSTOMERS = [
        { id: 'CUS-101', name: 'Nguyễn Hoàng Minh', email: 'hoangminh.design@gmail.com', phone: '0912 345 678', city: 'Hà Nội', ordersCount: 8, totalSpent: 9450000, tier: 'VIP Black Card', lastOrder: '2026-11-14' },
        { id: 'CUS-102', name: 'Trần Thảo Ly', email: 'thaoly.tran@outlook.com', phone: '0978 998 877', city: 'TP.HCM', ordersCount: 5, totalSpent: 4820000, tier: 'Gold Member', lastOrder: '2026-11-14' },
        { id: 'CUS-103', name: 'Lê Tuấn Hưng', email: 'tuanhung.le@gmail.com', phone: '0903 112 233', city: 'Đà Nẵng', ordersCount: 3, totalSpent: 2950000, tier: 'Silver Member', lastOrder: '2026-11-13' },
        { id: 'CUS-104', name: 'Phạm Đức Anh', email: 'ducanh.arch@gmail.com', phone: '0934 556 789', city: 'TP.HCM', ordersCount: 12, totalSpent: 16800000, tier: 'VIP Black Card', lastOrder: '2026-11-12' },
        { id: 'CUS-105', name: 'Đặng Ngọc Quỳnh', email: 'quynh.dang@gmail.com', phone: '0918 776 554', city: 'Cần Thơ', ordersCount: 2, totalSpent: 1120000, tier: 'Standard', lastOrder: '2026-11-08' }
    ];

    const SEED_COUPONS = [
        { id: 'CPN-01', code: 'VASTWELCOME', description: 'Ưu đãi chào mừng thành viên mới giảm 100k cho đơn từ 1 triệu', type: 'fixed', value: 100000, minOrder: 1000000, usageLimit: 500, usageCount: 284, expiryDate: '2026-12-31', status: 'active' },
        { id: 'CPN-02', code: 'FREESHIP', description: 'Miễn phí vận chuyển toàn quốc cho đơn từ 300k', type: 'fixed', value: 30000, minOrder: 300000, usageLimit: 1000, usageCount: 890, expiryDate: '2026-12-31', status: 'active' },
        { id: 'CPN-03', code: 'SUMMER50K', description: 'Giảm ngay 50.000₫ cho mọi đơn hàng mùa hè', type: 'fixed', value: 50000, minOrder: 500000, usageLimit: 300, usageCount: 195, expiryDate: '2026-11-30', status: 'active' },
        { id: 'CPN-04', code: 'VIPMEMBER', description: 'Đặc quyền thành viên VIP giảm 15% tổng hóa đơn', type: 'percent', value: 15, minOrder: 800000, usageLimit: 200, usageCount: 112, expiryDate: '2026-12-31', status: 'active' }
    ];

    const SEED_SETTINGS = {
        storeName: 'VAST STUDIO',
        tagline: 'Modern Streetwear & Minimalist Aesthetics',
        hotline: '0988.123.456',
        email: 'contact@vaststudio.vn',
        website: 'https://vaststudio.vn',
        addressHanoi: '148 Phố Huế, Q. Hai Bà Trưng, Hà Nội (Flagship Store)',
        addressHcm: '72 Lê Thánh Tôn, P. Bến Nghé, Quận 1, TP.HCM',
        freeShippingThreshold: 500000,
        defaultShippingFee: 30000,
        bankName: 'Vietcombank - Chi nhánh Thăng Long, Hà Nội',
        bankAccount: '9988 7766 5500',
        bankOwner: 'CONG TY TNHH VAST STUDIO VIETNAM'
    };

    // Model Implementation
    class StoreModel {
        constructor() {
            this.initSeeds();
        }

        initSeeds() {
            // Clear legacy cached product keys so browser displays pristine verified images
            ['vast_products_v1', 'vast_products_v2', 'vast_products_v3'].forEach(k => {
                try { localStorage.removeItem(k); } catch (e) {}
            });

            const storedProducts = localStorage.getItem(STORAGE_KEYS.PRODUCTS);
            if (!storedProducts) {
                localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(SEED_PRODUCTS));
            } else {
                try {
                    let currentProducts = JSON.parse(storedProducts);
                    if (Array.isArray(currentProducts)) {
                        const seedMap = new Map(SEED_PRODUCTS.map(p => [p.id, p]));
                        // Synchronize product images and core metadata with updated seeds
                        currentProducts = currentProducts.map(p => {
                            const seed = seedMap.get(p.id);
                            if (seed) {
                                return {
                                    ...p,
                                    image: seed.image,
                                    fallbackImage: seed.fallbackImage,
                                    name: seed.name,
                                    category: seed.category,
                                    description: seed.description
                                };
                            }
                            return p;
                        });
                        const existingIds = new Set(currentProducts.map(p => p.id));
                        for (const seed of SEED_PRODUCTS) {
                            if (!existingIds.has(seed.id)) {
                                currentProducts.push(seed);
                            }
                        }
                        localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(currentProducts));
                    }
                } catch (e) {
                    localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(SEED_PRODUCTS));
                }
            }
            if (!localStorage.getItem(STORAGE_KEYS.ORDERS)) {
                localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(SEED_ORDERS));
            }
            if (!localStorage.getItem(STORAGE_KEYS.COLLECTIONS)) {
                localStorage.setItem(STORAGE_KEYS.COLLECTIONS, JSON.stringify(SEED_COLLECTIONS));
            }
            if (!localStorage.getItem(STORAGE_KEYS.CUSTOMERS)) {
                localStorage.setItem(STORAGE_KEYS.CUSTOMERS, JSON.stringify(SEED_CUSTOMERS));
            }
            if (!localStorage.getItem(STORAGE_KEYS.COUPONS)) {
                localStorage.setItem(STORAGE_KEYS.COUPONS, JSON.stringify(SEED_COUPONS));
            }
            if (!localStorage.getItem(STORAGE_KEYS.SETTINGS)) {
                localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(SEED_SETTINGS));
            }
        }

        resetAll() {
            localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(SEED_PRODUCTS));
            localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(SEED_ORDERS));
            localStorage.setItem(STORAGE_KEYS.COLLECTIONS, JSON.stringify(SEED_COLLECTIONS));
            localStorage.setItem(STORAGE_KEYS.CUSTOMERS, JSON.stringify(SEED_CUSTOMERS));
            localStorage.setItem(STORAGE_KEYS.COUPONS, JSON.stringify(SEED_COUPONS));
            localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(SEED_SETTINGS));
        }

        // --- PRODUCT MODEL METHODS ---
        syncLiveCatalog(catalog) {
            if (!catalog || typeof catalog !== 'object') return;
            if (Array.isArray(catalog.products)) {
                localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(catalog.products));
            }
            if (Array.isArray(catalog.collections)) {
                localStorage.setItem(STORAGE_KEYS.COLLECTIONS, JSON.stringify(catalog.collections));
            }
            if (Array.isArray(catalog.coupons)) {
                localStorage.setItem(STORAGE_KEYS.COUPONS, JSON.stringify(catalog.coupons));
            }
            if (catalog.settings && typeof catalog.settings === 'object') {
                localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(catalog.settings));
            }
        }

        getVariantStock(product, size, color) {
            if (!product) return 0;
            if (Array.isArray(product.variants) && product.variants.length > 0) {
                const match = product.variants.find(v => v.size === size && v.color === color);
                if (match) return Math.max(0, Number(match.stock) || 0);
            }
            return Math.max(0, Number(product.stock) || 0);
        }

        getProducts() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.PRODUCTS) || '[]');
        }

        getProductById(id) {
            return this.getProducts().find(p => p.id === id || String(p.dbId) === String(id));
        }

        saveProduct(productData) {
            const products = this.getProducts();
            if (productData.id) {
                const idx = products.findIndex(p => p.id === productData.id);
                if (idx !== -1) {
                    products[idx] = { ...products[idx], ...productData };
                }
            } else {
                productData.id = 'PRD-' + String(Date.now()).slice(-4);
                productData.createdAt = new Date().toISOString().split('T')[0];
                productData.salesCount = 0;
                products.unshift(productData);
            }
            localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(products));
            return productData;
        }

        deleteProduct(id) {
            const filtered = this.getProducts().filter(p => p.id !== id);
            localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(filtered));
        }

        // --- ORDER MODEL METHODS ---
        getOrders() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.ORDERS) || '[]');
        }

        getOrderById(id) {
            return this.getOrders().find(o => o.id === id);
        }

        updateOrderStatus(orderId, newStatus) {
            const orders = this.getOrders();
            const order = orders.find(o => o.id === orderId);
            if (order) {
                order.orderStatus = newStatus;
                if (newStatus === 'completed' && order.paymentStatus !== 'Đã thanh toán') {
                    order.paymentStatus = 'Đã thanh toán';
                }
                localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(orders));
                return order;
            }
            return null;
        }

        createOrder(orderInput) {
            const orders = this.getOrders();
            const orderId = 'VAST-' + (Math.floor(1000 + Math.random() * 9000));
            const newOrder = {
                id: orderId,
                customerName: orderInput.customerName || 'Khách VAST',
                customerPhone: orderInput.customerPhone || '',
                customerEmail: orderInput.customerEmail || '',
                shippingAddress: orderInput.shippingAddress || '',
                items: orderInput.items || [],
                subtotal: orderInput.subtotal || 0,
                shippingFee: orderInput.shippingFee || 0,
                discount: orderInput.discount || 0,
                couponCode: orderInput.couponCode || null,
                total: orderInput.total || 0,
                paymentMethod: orderInput.paymentMethod || 'COD (Thanh toán khi nhận hàng)',
                paymentStatus: (orderInput.paymentMethod && orderInput.paymentMethod.includes('QR')) ? 'Đã thanh toán' : 'Chưa thanh toán',
                orderStatus: 'pending',
                note: orderInput.note || '',
                createdAt: new Date().toISOString().replace('T', ' ').substring(0, 16)
            };
            orders.unshift(newOrder);
            localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(orders));

            // Reduce product stock & increment salesCount
            const products = this.getProducts();
            (newOrder.items || []).forEach(item => {
                const prd = products.find(p => p.id === item.id);
                if (prd) {
                    prd.stock = Math.max(0, (prd.stock || 0) - (item.quantity || 1));
                    prd.salesCount = (prd.salesCount || 0) + (item.quantity || 1);
                }
            });
            localStorage.setItem(STORAGE_KEYS.PRODUCTS, JSON.stringify(products));

            // Clear cart
            this.clearCart();

            return newOrder;
        }

        getOrdersByPhone(phone) {
            const cleanPhone = (phone || '').replace(/\s+/g, '');
            if (!cleanPhone) return [];
            return this.getOrders().filter(o => (o.customerPhone || '').replace(/\s+/g, '').includes(cleanPhone));
        }

        cancelOrder(orderId) {
            const orders = this.getOrders();
            const order = orders.find(o => o.id === orderId);
            if (order && (order.orderStatus === 'pending' || order.orderStatus === 'processing')) {
                order.orderStatus = 'cancelled';
                localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(orders));
                return { success: true, order };
            }
            return { success: false, message: 'Đơn hàng không thể hủy hoặc không tồn tại' };
        }

        // --- REVIEWS METHODS ---
        getReviews() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.REVIEWS) || '{}');
        }

        getProductReviews(productId) {
            const allReviews = this.getReviews();
            if (allReviews[productId] && allReviews[productId].length > 0) {
                return allReviews[productId];
            }
            return [
                {
                    author: 'Hoàng Minh',
                    city: 'Hà Nội',
                    rating: 5,
                    date: '2026-11-12',
                    variant: 'Size L • Đen Washed',
                    content: 'Chất vải dệt rất đầm tay, mặc đứng form chuẩn boxy. Giặt máy không bị xù lông hay bai cổ áo, xứng đáng từng đồng.'
                },
                {
                    author: 'Thảo Ly',
                    city: 'TP.HCM',
                    rating: 5,
                    date: '2026-11-05',
                    variant: 'Size M • Trắng Vintage',
                    content: 'Giao hàng siêu nhanh, đóng gói hộp đen xịn sò như đồ luxury. Đường may trần đè 2 kim kỹ lưỡng, rất tôn dáng.'
                },
                {
                    author: 'Đức Anh',
                    city: 'Đà Nẵng',
                    rating: 5,
                    date: '2026-10-28',
                    variant: 'Size XL • Xám Ti-tan',
                    content: 'Phối với quần wide leg denim cực kỳ hợp gu. Sẽ tiếp tục ủng hộ VAST STUDIO ở các drop sau!'
                }
            ];
        }

        addReview(productId, reviewData) {
            const allReviews = this.getReviews();
            if (!allReviews[productId]) {
                allReviews[productId] = [...this.getProductReviews(productId)];
            }
            const newReview = {
                author: reviewData.author || 'Khách hàng VAST',
                city: reviewData.city || 'Việt Nam',
                rating: Number(reviewData.rating) || 5,
                date: new Date().toISOString().split('T')[0],
                variant: reviewData.variant || 'Tiêu chuẩn',
                content: reviewData.content || ''
            };
            allReviews[productId].unshift(newReview);
            localStorage.setItem(STORAGE_KEYS.REVIEWS, JSON.stringify(allReviews));
            return newReview;
        }

        // --- CART METHODS ---
        getCart() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.CART) || '[]');
        }

        saveCart(cart) {
            localStorage.setItem(STORAGE_KEYS.CART, JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('vast:cart-updated', { detail: cart }));
        }

        addToCart(product, size, color, quantity = 1) {
            const cart = this.getCart();
            const chosenSize = size || (product.sizes && product.sizes[0]) || 'M';
            const chosenColor = color || (product.colors && product.colors[0]) || 'Tiêu chuẩn';
            
            const existingIndex = cart.findIndex(item => 
                item.id === product.id && item.size === chosenSize && item.color === chosenColor
            );

            if (existingIndex > -1) {
                cart[existingIndex].quantity += quantity;
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    originalPrice: product.originalPrice,
                    image: product.image,
                    fallbackImage: product.fallbackImage,
                    size: chosenSize,
                    color: chosenColor,
                    quantity: quantity
                });
            }

            this.saveCart(cart);
            return cart;
        }

        updateCartQuantity(index, quantity) {
            const cart = this.getCart();
            if (cart[index]) {
                if (quantity <= 0) {
                    cart.splice(index, 1);
                } else {
                    cart[index].quantity = quantity;
                }
                this.saveCart(cart);
            }
            return cart;
        }

        removeFromCart(index) {
            const cart = this.getCart();
            if (cart[index]) {
                cart.splice(index, 1);
                this.saveCart(cart);
            }
            return cart;
        }

        clearCart() {
            this.saveCart([]);
        }

        getCartCount() {
            return this.getCart().reduce((sum, item) => sum + (item.quantity || 1), 0);
        }

        getCartSubtotal() {
            return this.getCart().reduce((sum, item) => sum + (item.price * (item.quantity || 1)), 0);
        }

        // --- WISHLIST METHODS ---
        getWishlist() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.WISHLIST) || '[]');
        }

        toggleWishlist(productId) {
            let wishlist = this.getWishlist();
            const exists = wishlist.includes(productId);
            if (exists) {
                wishlist = wishlist.filter(id => id !== productId);
            } else {
                wishlist.push(productId);
            }
            localStorage.setItem(STORAGE_KEYS.WISHLIST, JSON.stringify(wishlist));
            window.dispatchEvent(new CustomEvent('vast:wishlist-updated', { detail: wishlist }));
            return !exists;
        }

        isInWishlist(productId) {
            return this.getWishlist().includes(productId);
        }

        // --- COUPON VALIDATION ---
        validateCoupon(code, subtotal) {
            if (!code) return { valid: false, message: 'Vui lòng nhập mã giảm giá' };
            const coupons = this.getCoupons();
            const coupon = coupons.find(c => c.code.toUpperCase() === code.trim().toUpperCase() && c.status === 'active');
            
            if (!coupon) {
                return { valid: false, message: 'Mã giảm giá không hợp lệ hoặc đã hết hạn' };
            }
            if (subtotal < coupon.minOrder) {
                return { 
                    valid: false, 
                    message: `Mã ${coupon.code} chỉ áp dụng cho đơn hàng từ ${coupon.minOrder.toLocaleString('vi-VN')}₫` 
                };
            }

            let discount = 0;
            if (coupon.type === 'fixed') {
                discount = coupon.value;
            } else if (coupon.type === 'percent') {
                discount = Math.round(subtotal * (coupon.value / 100));
            }
            return { valid: true, coupon, discount };
        }

        // --- COLLECTION MODEL METHODS ---
        getCollections() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.COLLECTIONS) || '[]');
        }

        saveCollection(colData) {
            const list = this.getCollections();
            colData.id = 'COL-' + String(Date.now()).slice(-3);
            colData.productsCount = 0;
            list.push(colData);
            localStorage.setItem(STORAGE_KEYS.COLLECTIONS, JSON.stringify(list));
            return colData;
        }

        // --- CUSTOMER MODEL & AUTH METHODS ---
        getCustomers() {
            const customers = JSON.parse(localStorage.getItem(STORAGE_KEYS.CUSTOMERS) || '[]');
            let removedPasswords = false;
            const safeCustomers = customers.map(customer => {
                if (Object.prototype.hasOwnProperty.call(customer, 'password')) {
                    const safeCustomer = { ...customer };
                    delete safeCustomer.password;
                    removedPasswords = true;
                    return safeCustomer;
                }
                return customer;
            });
            if (removedPasswords) {
                localStorage.setItem(STORAGE_KEYS.CUSTOMERS, JSON.stringify(safeCustomers));
            }
            return safeCustomers;
        }

        getCurrentUser() {
            try {
                const user = JSON.parse(localStorage.getItem('vast_auth_user') || 'null');
                if (user && Object.prototype.hasOwnProperty.call(user, 'password')) {
                    delete user.password;
                    localStorage.setItem('vast_auth_user', JSON.stringify(user));
                }
                return user;
            } catch (e) {
                return null;
            }
        }

        setCurrentUser(user) {
            if (user) {
                localStorage.setItem('vast_auth_user', JSON.stringify(user));
            } else {
                localStorage.removeItem('vast_auth_user');
            }
            window.dispatchEvent(new CustomEvent('vast:auth-updated', { detail: user }));
        }

        async refreshCustomerSession() {
            const response = await fetch('auth-api.php?action=session', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thể kiểm tra phiên đăng nhập.');
            }
            const container = document.getElementById('auth-main-container');
            if (container && result.csrf) container.dataset.authCsrf = result.csrf;
            this.setCurrentUser(result.user || null);
            return result;
        }

        async requestCustomerAuth(action, data = {}) {
            const container = document.getElementById('auth-main-container');
            const response = await fetch('auth-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': container?.dataset.authCsrf || '',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...data, action })
            });
            const result = await response.json();
            if (container && result.csrf) container.dataset.authCsrf = result.csrf;
            if (response.ok && result.success) {
                this.setCurrentUser(result.user || null);
            }
            return result;
        }

        registerCustomer(data) {
            return this.requestCustomerAuth('register', data);
        }

        loginCustomer(identifier, password) {
            return this.requestCustomerAuth('login', { identifier, password });
        }

        logoutCustomer() {
            return this.requestCustomerAuth('logout');
        }

        updateCustomerProfile(id, updatedData) {
            const customers = this.getCustomers();
            const index = customers.findIndex(c => c.id === id);
            if (index > -1) {
                customers[index] = { ...customers[index], ...updatedData };
                localStorage.setItem(STORAGE_KEYS.CUSTOMERS, JSON.stringify(customers));
                
                const current = this.getCurrentUser();
                if (current && current.id === id) {
                    this.setCurrentUser(customers[index]);
                }
                return { success: true, user: customers[index] };
            }
            return { success: false, message: 'Không tìm thấy người dùng' };
        }

        // --- COUPON MODEL METHODS ---
        getCoupons() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.COUPONS) || '[]');
        }

        saveCoupon(couponData) {
            const list = this.getCoupons();
            couponData.id = 'CPN-' + String(Date.now()).slice(-3);
            couponData.usageCount = 0;
            list.unshift(couponData);
            localStorage.setItem(STORAGE_KEYS.COUPONS, JSON.stringify(list));
            return couponData;
        }

        deleteCoupon(id) {
            const list = this.getCoupons().filter(c => c.id !== id);
            localStorage.setItem(STORAGE_KEYS.COUPONS, JSON.stringify(list));
        }

        // --- SETTING MODEL METHODS ---
        getSettings() {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.SETTINGS) || '{}');
        }

        saveSettings(updated) {
            const current = this.getSettings();
            const merged = { ...current, ...updated };
            localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(merged));
            return merged;
        }
    }

    // Expose Model to global scope
    window.StoreModel = new StoreModel();
    window.createFashionPlaceholder = createFashionPlaceholder;

})(window);

