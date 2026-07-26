<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Muthoni's Laundry · Fresh & Fast</title>
    <!-- Font Awesome 6 (minimal) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f6f8fc;
            color: #1e293b;
            line-height: 1.5;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ===== TOP BAR ===== */
        .top-bar {
            background: #0b1e3a;
            color: #94a3b8;
            font-size: 12px;
            padding: 8px 0;
            text-align: center;
            border-bottom: 1px solid #1e3a5f;
        }

        .top-bar i {
            color: #3b7cff;
            margin-right: 6px;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            padding: 12px 0;
            border-bottom: 1px solid #e9edf4;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 20px;
            color: #1e293b;
        }

        .logo i {
            color: #3b7cff;
            font-size: 24px;
            background: #eef3ff;
            padding: 8px;
            border-radius: 12px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            font-weight: 500;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #475569;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #3b7cff;
        }

        .nav-divider {
            color: #d0d9e8;
            font-weight: 300;
        }

        .btn-outline-sm {
            border: 1px solid #d0d9e8;
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 600;
            color: #1e293b;
            background: white;
        }

        .btn-outline-sm:hover {
            background: #f1f6ff;
            border-color: #3b7cff;
            color: #3b7cff;
        }

        .btn-primary-sm {
            background: #3b7cff;
            color: white;
            padding: 8px 22px;
            border-radius: 40px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(59, 124, 255, 0.2);
        }

        .btn-primary-sm:hover {
            background: #2a66e0;
            transform: translateY(-1px);
        }

        .staff-badge {
            font-size: 11px;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 40px;
            color: #64748b;
        }

        /* ===== HERO ===== */
        .hero {
            padding: 60px 0 70px;
            background: linear-gradient(145deg, #f6faff, #ffffff);
        }

        .hero .container {
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .hero-content {
            flex: 1 1 400px;
        }

        .hero-content .badge {
            display: inline-block;
            background: #eef3ff;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            color: #3b7cff;
            margin-bottom: 20px;
        }

        .hero-content h1 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 16px;
            color: #0b1e3a;
        }

        .hero-content p {
            font-size: 17px;
            color: #475569;
            max-width: 460px;
            margin-bottom: 28px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-primary {
            background: #3b7cff;
            color: white;
            padding: 12px 32px;
            border-radius: 40px;
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(59, 124, 255, 0.25);
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #2a66e0;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: #1e293b;
            padding: 12px 32px;
            border-radius: 40px;
            font-weight: 600;
            border: 1px solid #d0d9e8;
            transition: 0.2s;
        }

        .btn-secondary:hover {
            background: #f1f6ff;
            border-color: #3b7cff;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .hero-stats div {
            display: flex;
            flex-direction: column;
        }

        .hero-stats span {
            font-weight: 700;
            font-size: 28px;
            color: #0b1e3a;
        }

        .hero-stats small {
            color: #64748b;
            font-size: 13px;
        }

        /* ===== HERO IMAGE ===== */
        .hero-image {
            flex: 1 1 380px;
            border-radius: 24px;
            overflow: hidden;
            min-height: 280px;
            background: #dce5f5;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            min-height: 280px;
            object-fit: cover;
            display: block;
        }

        .hero-image .fallback {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            min-height: 280px;
            background: linear-gradient(145deg, #dce5f5, #c8d6f0);
            color: #1a2f5a;
            padding: 20px;
            text-align: center;
        }

        .hero-image .fallback i {
            font-size: 48px;
            color: #1a2f5a;
            opacity: 0.4;
            margin-bottom: 12px;
        }

        .hero-image .fallback span {
            font-weight: 500;
            font-size: 14px;
            opacity: 0.7;
        }

        /* ===== SECTIONS ===== */
        .section-head {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-head h2 {
            font-size: 28px;
            font-weight: 700;
            color: #0b1e3a;
        }

        .section-head p {
            color: #64748b;
            margin-top: 8px;
            font-size: 15px;
        }

        /* ===== SERVICES - 2 COLUMN (CLICKABLE LIST, NO ICONS) ===== */
        .services {
            padding: 60px 0 40px;
        }

        .service-list-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .service-item {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            border: 1px solid #eef2f8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .service-item:hover {
            transform: translateY(-3px);
            border-color: #3b7cff;
            box-shadow: 0 8px 24px rgba(59, 124, 255, 0.10);
        }

        .service-item-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .service-item .info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .service-item .info .desc {
            font-size: 13px;
            color: #94a3b8;
        }

        .service-item .pricing {
            text-align: right;
        }

        .service-item .pricing .price {
            font-weight: 700;
            font-size: 18px;
            color: #0b1e3a;
        }

        .service-item .pricing .unit {
            font-size: 12px;
            color: #94a3b8;
        }

        .service-item .click-hint {
            font-size: 12px;
            color: #3b7cff;
            opacity: 0;
            transition: 0.2s;
            margin-top: 4px;
        }

        .service-item:hover .click-hint {
            opacity: 1;
        }

        .service-item .tag-sm {
            display: inline-block;
            background: #eaf6ea;
            color: #1f7b4d;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 40px;
            margin-left: 8px;
        }

        /* ===== SERVICE DETAIL MODAL (NO ICON BOX) ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            z-index: 999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 28px;
            max-width: 520px;
            width: 100%;
            padding: 40px 36px;
            position: relative;
            animation: modalFade 0.25s ease;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.20);
        }

        @keyframes modalFade {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(12px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
            transition: 0.2s;
        }

        .modal-close:hover {
            color: #1e293b;
            transform: rotate(90deg);
        }

        /* modal-icon completely removed */

        .modal-box h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .modal-box .modal-sub {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .modal-box .modal-price {
            font-size: 28px;
            font-weight: 700;
            color: #0b1e3a;
            margin-bottom: 4px;
        }

        .modal-box .modal-unit {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .modal-box .modal-desc {
            color: #475569;
            font-size: 15px;
            line-height: 1.6;
            padding-top: 16px;
            border-top: 1px solid #eef2f8;
        }

        .modal-box .btn-order {
            display: inline-block;
            margin-top: 20px;
            background: #3b7cff;
            color: white;
            padding: 12px 32px;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .modal-box .btn-order:hover {
            background: #2a66e0;
            transform: translateY(-2px);
        }

        /* ===== HOW IT WORKS ===== */
        .how {
            background: #ffffff;
            padding: 60px 0;
            border-top: 1px solid #eef2f8;
            border-bottom: 1px solid #eef2f8;
        }

        .steps {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .step {
            flex: 1 1 180px;
            background: #fafcff;
            border-radius: 24px;
            padding: 28px 16px;
            text-align: center;
            border: 1px solid #edf2fa;
        }

        .step .num {
            background: #3b7cff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 14px;
        }

        .step h4 {
            font-weight: 600;
            font-size: 17px;
            margin-bottom: 6px;
        }

        .step p {
            font-size: 14px;
            color: #64748b;
        }

        /* ===== WHY CHOOSE ===== */
        .why {
            padding: 60px 0 40px;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }

        .why-item {
            background: white;
            padding: 24px 16px;
            border-radius: 20px;
            border: 1px solid #eef2f8;
            text-align: center;
        }

        .why-item i {
            font-size: 24px;
            color: #3b7cff;
            margin-bottom: 12px;
            background: #eef3ff;
            padding: 12px;
            border-radius: 16px;
        }

        .why-item h4 {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .why-item p {
            font-size: 13px;
            color: #64748b;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials {
            background: #fafcff;
            padding: 60px 0;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }

        .testimonial-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            border: 1px solid #eef2f8;
        }

        .testimonial-card .stars {
            color: #fbbf24;
            letter-spacing: 2px;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .testimonial-card blockquote {
            font-style: italic;
            font-size: 14px;
            color: #334155;
            margin-bottom: 12px;
        }

        .testimonial-card .author {
            font-weight: 600;
            font-size: 13px;
            color: #0b1e3a;
        }

        .testimonial-card .location {
            font-size: 12px;
            color: #94a3b8;
        }

        /* ===== CTA BANNER ===== */
        .cta-banner {
            background: #0b1e3a;
            padding: 50px 0;
            color: white;
            text-align: center;
        }

        .cta-banner h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .cta-banner p {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 24px;
        }

        .cta-banner .btn-white {
            background: white;
            color: #0b1e3a;
            padding: 12px 36px;
            border-radius: 40px;
            font-weight: 600;
            transition: 0.2s;
        }

        .cta-banner .btn-white:hover {
            background: #eef3ff;
            transform: scale(1.02);
        }

        /* ===== ACCESS SECTION REMOVED ===== */

        /* ===== FOOTER ===== */
        .footer {
            background: #ffffff;
            padding: 40px 0 24px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-col h4 {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 12px;
            color: #0b1e3a;
        }

        .footer-col p,
        .footer-col a {
            font-size: 13px;
            color: #64748b;
            display: block;
            margin-bottom: 6px;
        }

        .footer-col a:hover {
            color: #3b7cff;
        }

        .footer-bottom {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            font-size: 13px;
            color: #94a3b8;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 700px) {
            .container {
                padding: 0 16px;
            }
            .top-bar {
                font-size: 11px;
                padding: 6px 0;
            }
            .hero {
                padding: 28px 0 32px;
            }
            .hero .container {
                flex-direction: column;
                text-align: center;
                gap: 22px;
            }
            .hero-content h1 {
                font-size: 30px;
            }
            .hero-content p {
                max-width: 100%;
                margin-bottom: 18px;
            }
            .hero-actions {
                justify-content: center;
            }
            .hero-stats {
                justify-content: center;
                gap: 22px;
                margin-top: 20px;
                padding-top: 14px;
            }
            .hero-stats span {
                font-size: 22px;
            }
            .nav-links {
                gap: 10px;
                justify-content: center;
                margin-top: 8px;
            }
            .navbar .container {
                flex-direction: column;
            }
            .section-head {
                margin-bottom: 24px;
            }
            .section-head h2 {
                font-size: 22px;
            }
            .services {
                padding: 32px 0 24px;
            }
            .how {
                padding: 32px 0;
            }
            .why {
                padding: 32px 0 24px;
            }
            .testimonials {
                padding: 32px 0;
            }
            .cta-banner {
                padding: 32px 0;
            }
            .footer {
                padding: 28px 0 18px;
            }
            .steps {
                gap: 14px;
            }
            .step {
                padding: 20px 14px;
                flex: 1 1 130px;
            }
            .grid-4 {
                gap: 14px;
            }
            .why-item {
                padding: 18px 12px;
            }
            .testimonial-grid {
                gap: 14px;
            }
            .testimonial-card {
                padding: 18px;
            }
            .service-list-2col {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .service-item {
                flex-wrap: wrap;
                gap: 10px;
                padding: 16px 18px;
            }
            .service-item .pricing {
                text-align: left;
                width: 100%;
                margin-left: 0;
            }
            .modal-box {
                padding: 28px 20px;
            }
            .hero-image {
                min-height: 170px;
                width: 100%;
                order: -1;
            }
            .hero-image img {
                min-height: 170px;
            }
            .footer-grid {
                gap: 20px;
                margin-bottom: 20px;
            }
            .footer-bottom {
                justify-content: center;
                text-align: center;
                gap: 10px;
            }
        }

        @media (max-width: 420px) {
            .hero-content h1 {
                font-size: 26px;
            }
            .hero-stats {
                gap: 16px;
            }
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            .hero-actions a {
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== TOP BAR ===== -->
    <div class="top-bar">
        <i class="fas fa-phone"></i> Call or WhatsApp: 0700-000-000 &nbsp;|&nbsp; Open Mon–Sat, 8am–6pm
    </div>

    <!-- ===== NAVBAR ===== -->
    <header class="navbar">
        <div class="container">
            <div class="logo">
                <i class="fas fa-tshirt"></i> Muthoni's Laundry
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="#services">Services</a>
                <a href="#how">How It Works</a>
                <span class="nav-divider">|</span>
                <a href="customer_login.php" class="btn-outline-sm">Customer</a>
                <a href="login.php" class="staff-badge">Staff</a>
                <a href="track_guest.php" class="btn-primary-sm">Track</a>
            </div>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <span class="badge">Nairobi's favourite</span>
                <h1>Fresh clothes,<br />happy life.</h1>
                <p>We wash, dry, fold and deliver — so you can focus on what matters. Simple pricing, fast turnaround.</p>
                <div class="hero-actions">
                    <a href="customer_login.php?mode=register" class="btn-primary">Create account</a>
                    <a href="track_guest.php" class="btn-secondary">Track order</a>
                </div>
                <div class="hero-stats">
                    <div><span>2,400+</span> <small>orders done</small></div>
                    <div><span>98%</span> <small>happy customers</small></div>
                    <div><span>24h</span> <small>avg. turnaround</small></div>
                </div>
            </div>

            <!-- ===== HERO IMAGE ===== -->
            <div class="hero-image">
                <!-- REPLACE THE src URL BELOW WITH YOUR OWN LAUNDRY IMAGE -->
                <img
                src="https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=600&h=400&fit=crop&crop=center"
                alt="Modern laundry facility with washing machines"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                />
                <div class="fallback" style="display:none;">
                    <i class="fas fa-tshirt"></i>
                    <span>Laundry facility image</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SERVICES - 2 COLUMN CLICKABLE LIST (NO AVATARS) ===== -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-head">
                <h2>Our Services &amp; Pricing</h2>
                <p>Click any service to see full details</p>
            </div>
            <div class="service-list-2col">

                <div class="service-item" data-service="wash-fold">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Wash &amp; Fold <span class="tag-sm">popular</span></h3>
                            <div class="desc">Professional wash &amp; neat folding</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 450</div>
                        <div class="unit">per 5kg bag</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

                <div class="service-item" data-service="ironing">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Ironing <span class="tag-sm">crisp</span></h3>
                            <div class="desc">Professional pressing &amp; steaming</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 120</div>
                        <div class="unit">per item</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

                <div class="service-item" data-service="dry-clean">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Dry Cleaning <span class="tag-sm">delicate</span></h3>
                            <div class="desc">Special care for delicate fabrics</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 750</div>
                        <div class="unit">per garment</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

                <div class="service-item" data-service="bulk">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Bulk Laundry <span class="tag-sm">save 15%</span></h3>
                            <div class="desc">Perfect for families &amp; large loads</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 1,200</div>
                        <div class="unit">10kg+</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

                <div class="service-item" data-service="pickup">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Pickup &amp; Delivery <span class="tag-sm">convenient</span></h3>
                            <div class="desc">We come to your doorstep</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 200</div>
                        <div class="unit">within Nairobi</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

                <div class="service-item" data-service="premium">
                    <div class="service-item-left">
                        <div class="info">
                            <h3>Premium Care <span class="tag-sm">VIP</span></h3>
                            <div class="desc">Full-service luxury treatment</div>
                        </div>
                    </div>
                    <div class="pricing">
                        <div class="price">Ksh 1,500</div>
                        <div class="unit">full service</div>
                        <div class="click-hint">details →</div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== SERVICE DETAIL MODAL (NO ICON BOX) ===== -->
    <div class="modal-overlay" id="serviceModal">
        <div class="modal-box">
            <button class="modal-close" id="modalClose">&times;</button>
            <!-- modal-icon removed -->
            <h2 id="modalTitle">Wash &amp; Fold</h2>
            <div class="modal-sub" id="modalTag">popular</div>
            <div class="modal-price" id="modalPrice">Ksh 450</div>
            <div class="modal-unit" id="modalUnit">per 5kg bag</div>
            <div class="modal-desc" id="modalDesc">
                We wash your clothes with high-quality detergents, dry them carefully, and fold them neatly.
                Perfect for everyday laundry. Free pickup available for orders above Ksh 1,000.
            </div>
            <a href="customer_login.php?mode=register" class="btn-order">Order this service</a>
        </div>
    </div>

    <!-- ===== HOW IT WORKS ===== -->
    <section class="how" id="how">
        <div class="container">
            <div class="section-head">
                <h2>How it works</h2>
                <p>Four simple steps to fresh laundry</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="num">1</div>
                    <h4>Create account</h4>
                    <p>Sign up in 1 minute with your phone</p>
                </div>
                <div class="step">
                    <div class="num">2</div>
                    <h4>Place order</h4>
                    <p>Choose service &amp; pickup time</p>
                </div>
                <div class="step">
                    <div class="num">3</div>
                    <h4>We clean</h4>
                    <p>Wash, dry, fold with care</p>
                </div>
                <div class="step">
                    <div class="num">4</div>
                    <h4>Collect</h4>
                    <p>Pickup or get it delivered</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== WHY CHOOSE ===== -->
    <section class="why">
        <div class="container">
            <div class="section-head">
                <h2>Why choose us</h2>
                <p>We make laundry easy, reliable and affordable</p>
            </div>
            <div class="grid-4">
                <div class="why-item">
                    <i class="fas fa-clock"></i>
                    <h4>24h turnaround</h4>
                    <p>Most orders ready next day</p>
                </div>
                <div class="why-item">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h4>Careful handling</h4>
                    <p>Your clothes are in good hands</p>
                </div>
                <div class="why-item">
                    <i class="fas fa-tags"></i>
                    <h4>Transparent pricing</h4>
                    <p>No hidden fees, simple rates</p>
                </div>
                <div class="why-item">
                    <i class="fas fa-truck"></i>
                    <h4>Pickup &amp; delivery</h4>
                    <p>We come to you, anywhere in Nairobi</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONIALS ===== -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-head">
                <h2>Real reviews from real customers</h2>
                <p>People love the freshness and convenience</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <blockquote>"My clothes always come back smelling amazing and folded perfectly. I'll never wash at home again!"</blockquote>
                    <div class="author">Wanjiku M.</div>
                    <div class="location">Kilimani</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <blockquote>"Pickup and delivery saves me hours every week. Fair prices and really friendly staff."</blockquote>
                    <div class="author">Otieno O.</div>
                    <div class="location">South B</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <blockquote>"I use the bulk service for my whole family. Everything comes back fresh and on time."</blockquote>
                    <div class="author">Grace N.</div>
                    <div class="location">Westlands</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA BANNER ===== -->
    <section class="cta-banner">
        <div class="container">
            <h2>Ready to experience fresh?</h2>
            <p>Create a free account and place your first order today.</p>
            <a href="customer_login.php?mode=register" class="btn-white">Get started now</a>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>Muthoni's Laundry</h4>
                    <p>Fresh clothes, happy life. Trusted laundry service in Nairobi.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick links</h4>
                    <a href="index.php">Home</a>
                    <a href="#services">Services</a>
                    <a href="#how">How it works</a>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <a href="tel:0700000000">0700 000 000</a>
                    <a href="mailto:hello@muthonilaundry.com">hello@muthonilaundry.com</a>
                    <a href="#">Nairobi, Kenya</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; 2025 Muthoni's Laundry — all rights reserved.</span>
                <span style="display: flex; gap: 16px;">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- ===== MODAL JAVASCRIPT (updated to remove icon reference) ===== -->
    <script>
        const serviceData = {
            'wash-fold': {
                title: 'Wash & Fold',
                tag: 'popular',
                price: 'Ksh 450',
                unit: 'per 5kg bag',
                desc: 'We wash your clothes with high-quality detergents, dry them carefully, and fold them neatly. Perfect for everyday laundry. Free pickup available for orders above Ksh 1,000.'
            },
            'ironing': {
                title: 'Ironing',
                tag: 'crisp finish',
                price: 'Ksh 120',
                unit: 'per item',
                desc: 'Professional pressing and steaming service. We use high-quality irons and steamers to remove wrinkles and give your clothes a crisp, fresh look.'
            },
            'dry-clean': {
                title: 'Dry Cleaning',
                tag: 'delicate care',
                price: 'Ksh 750',
                unit: 'per garment',
                desc: 'Specialized dry cleaning for delicate fabrics that cannot be washed with water. We use gentle solvents to clean suits, dresses, and other delicate items.'
            },
            'bulk': {
                title: 'Bulk Laundry',
                tag: 'save 15%',
                price: 'Ksh 1,200',
                unit: '10kg+',
                desc: 'Perfect for families and large loads. Save 15% compared to standard pricing. Includes wash, dry, and fold. Free pickup and delivery for bulk orders.'
            },
            'pickup': {
                title: 'Pickup & Delivery',
                tag: 'convenient',
                price: 'Ksh 200',
                unit: 'within Nairobi',
                desc: 'We come to your doorstep to pick up your laundry and deliver it back to you once it\'s clean. Available within Nairobi. Free for orders above Ksh 1,500.'
            },
            'premium': {
                title: 'Premium Care',
                tag: 'VIP',
                price: 'Ksh 1,500',
                unit: 'full service',
                desc: 'Our premium full-service package includes wash, dry, fold, ironing, and special stain treatment. We also use premium detergents and fabric softeners for a luxury finish.'
            }
        };

        const modal = document.getElementById('serviceModal');
        const modalClose = document.getElementById('modalClose');
        const modalTitle = document.getElementById('modalTitle');
        const modalTag = document.getElementById('modalTag');
        const modalPrice = document.getElementById('modalPrice');
        const modalUnit = document.getElementById('modalUnit');
        const modalDesc = document.getElementById('modalDesc');

        document.querySelectorAll('.service-item').forEach(item => {
            item.addEventListener('click', function() {
                const key = this.dataset.service;
                const data = serviceData[key];
                if (data) {
                    modalTitle.textContent = data.title;
                    modalTag.textContent = data.tag;
                    modalPrice.textContent = data.price;
                    modalUnit.textContent = data.unit;
                    modalDesc.textContent = data.desc;
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>

</body>
</html>