<?php

namespace Modules\Catalog\Install;

/** Data demo realistis (Bahasa Indonesia). Tanggal relatif terhadap Okt 2026. */
final class DemoCatalog
{
    public function categories(): array
    {
        return [
            ['slug' => 'manajemen-proyek', 'title' => 'Manajemen Proyek', 'description' => 'Perencanaan, eksekusi, dan pengendalian proyek dengan standar internasional maupun skema BNSP.'],
            ['slug' => 'keamanan-informasi', 'title' => 'Keamanan Informasi', 'description' => 'Tata kelola dan teknis keamanan siber, dari ISO/IEC 27001 hingga penanganan insiden.'],
            ['slug' => 'data-analitik', 'title' => 'Data & Analitik', 'description' => 'Mengolah data menjadi keputusan bisnis dengan SQL, Python, dan visualisasi.'],
            ['slug' => 'k3', 'title' => 'K3 / Keselamatan Kerja', 'description' => 'Keselamatan dan kesehatan kerja untuk industri, konstruksi, dan perkantoran.'],
            ['slug' => 'digital-marketing', 'title' => 'Digital Marketing', 'description' => 'Strategi pemasaran digital, iklan berbayar, SEO, dan analitik kampanye.'],
        ];
    }

    public function programs(): array
    {
        return [
            [
                'slug' => 'manajemen-proyek-profesional',
                'data' => [
                    'title' => 'Manajemen Proyek Profesional',
                    'sku' => 'CRT-MPP-01',
                    'category' => 'manajemen-proyek',
                    'issuer' => 'internal',
                    'level' => 'Menengah',
                    'duration' => '5 hari (40 JP)',
                    'summary' => 'Kuasai siklus hidup proyek end-to-end: inisiasi, perencanaan jadwal & biaya, manajemen risiko, hingga penutupan proyek, selaras dengan PMBOK edisi terbaru.',
                    'description' => "## Tentang Program\n\nProgram intensif lima hari untuk praktisi yang ingin mengelola proyek secara terstruktur. Peserta mengerjakan studi kasus proyek nyata dari industri konstruksi, TI, dan manufaktur.\n\n### Materi Utama\n\n- Project charter & stakeholder register\n- Work Breakdown Structure (WBS) dan estimasi\n- Penjadwalan (CPM) dan Earned Value Management\n- Manajemen risiko & perubahan\n- Pendekatan hybrid: prediktif + agile\n\nPeserta yang lulus ujian akhir memperoleh **Sertifikat Kompetensi Manajemen Proyek** dan 35 PDU.",
                    'benefits' => ['Sertifikat kompetensi berlaku 3 tahun', '35 PDU untuk persiapan ujian PMP', 'Template dokumen proyek siap pakai', 'Akses grup alumni & sesi mentoring'],
                    'requirements' => ['Pengalaman kerja minimal 1 tahun', 'Membawa laptop'],
                    'audience' => ['Project Manager', 'Project Coordinator', 'Team Lead', 'PMO Staff'],
                    'price' => 7500000,
                    'promo_price' => 6250000,
                    'promo_until' => '2026-11-15',
                    'featured' => true,
                ],
                'batches' => [
                    ['code' => 'MPP-2611', 'start_date' => '2026-11-16', 'end_date' => '2026-11-20', 'mode' => 'offline', 'location' => 'Jakarta Selatan', 'capacity' => 25, 'status' => 'open'],
                    ['code' => 'MPP-2701', 'start_date' => '2027-01-18', 'end_date' => '2027-01-22', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 40, 'status' => 'open'],
                    ['code' => 'MPP-2703', 'start_date' => '2027-03-08', 'end_date' => '2027-03-12', 'mode' => 'hybrid', 'location' => 'Surabaya', 'capacity' => 30, 'status' => 'open'],
                ],
            ],
            [
                'slug' => 'bnsp-manajer-proyek',
                'data' => [
                    'title' => 'Sertifikasi BNSP Manajer Proyek',
                    'sku' => 'BNSP-PM-01',
                    'category' => 'manajemen-proyek',
                    'issuer' => 'bnsp',
                    'level' => 'Lanjut',
                    'duration' => '4 hari (32 JP) + uji kompetensi',
                    'summary' => 'Pelatihan dan uji kompetensi skema Manajer Proyek oleh LSP berlisensi BNSP. Sertifikat diakui nasional dan sesuai SKKNI.',
                    'description' => "## Skema Sertifikasi\n\nProgram ini terdiri dari pelatihan berbasis kompetensi (3 hari) dan **uji kompetensi** (1 hari) oleh asesor LSP berlisensi BNSP.\n\nUnit kompetensi mencakup perencanaan lingkup, jadwal, biaya, mutu, SDM, komunikasi, risiko, dan pengadaan proyek sesuai SKKNI.\n\nSertifikat BNSP berlaku **3 tahun** dan dapat diperpanjang melalui resertifikasi.",
                    'benefits' => ['Sertifikat kompetensi BNSP berlogo Garuda', 'Pendampingan penyusunan portofolio', 'Simulasi wawancara asesmen'],
                    'requirements' => ['Pendidikan minimal D3', 'Pengalaman mengelola proyek minimal 3 tahun', 'Portofolio proyek (dibantu fasilitator)', 'Pas foto berlatar merah'],
                    'audience' => ['Project Manager', 'Site Manager', 'Konsultan Proyek'],
                    'price' => 9500000,
                    'featured' => false,
                ],
                'batches' => [
                    ['code' => 'BPM-2612', 'start_date' => '2026-12-07', 'end_date' => '2026-12-10', 'mode' => 'offline', 'location' => 'Jakarta Pusat', 'capacity' => 20, 'status' => 'open'],
                    ['code' => 'BPM-2702', 'start_date' => '2027-02-15', 'end_date' => '2027-02-18', 'mode' => 'offline', 'location' => 'Bandung', 'capacity' => 20, 'status' => 'open', 'price_override' => 8950000],
                ],
            ],
            [
                'slug' => 'iso-27001-lead-implementer',
                'data' => [
                    'title' => 'ISO/IEC 27001 Lead Implementer',
                    'sku' => 'CRT-ISMS-LI',
                    'category' => 'keamanan-informasi',
                    'issuer' => 'internal',
                    'level' => 'Lanjut',
                    'duration' => '5 hari (40 JP)',
                    'summary' => 'Rancang dan implementasikan Sistem Manajemen Keamanan Informasi (SMKI) berbasis ISO/IEC 27001:2022, dari analisis gap hingga siap audit sertifikasi.',
                    'description' => "## Ringkasan\n\nPeserta mempelajari cara membangun SMKI dari nol: menentukan ruang lingkup, menyusun kebijakan, melakukan *risk assessment*, memilih kontrol Annex A, hingga mempersiapkan audit internal.\n\n### Hasil Belajar\n\n1. Menyusun *Statement of Applicability* (SoA)\n2. Melakukan penilaian & penanganan risiko\n3. Menyiapkan dokumen wajib ISO/IEC 27001:2022\n4. Memimpin proyek implementasi SMKI",
                    'benefits' => ['Toolkit dokumen SMKI (kebijakan, prosedur, SoA)', 'Studi kasus sektor perbankan & pemerintahan', 'Sertifikat Lead Implementer'],
                    'requirements' => ['Memahami dasar keamanan informasi', 'Pengalaman kerja di bidang TI/GRC minimal 2 tahun'],
                    'audience' => ['IT Security Officer', 'GRC/Compliance Officer', 'IT Manager', 'Konsultan'],
                    'price' => 12500000,
                    'featured' => true,
                ],
                'batches' => [
                    ['code' => 'ISMS-2611', 'start_date' => '2026-11-23', 'end_date' => '2026-11-27', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 30, 'status' => 'closed'],
                    ['code' => 'ISMS-2702', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05', 'mode' => 'offline', 'location' => 'Jakarta Selatan', 'capacity' => 20, 'status' => 'open'],
                ],
            ],
            [
                'slug' => 'bnsp-junior-cyber-security',
                'data' => [
                    'title' => 'Sertifikasi BNSP Junior Cyber Security',
                    'sku' => 'BNSP-JCS-01',
                    'category' => 'keamanan-informasi',
                    'issuer' => 'bnsp',
                    'level' => 'Dasar',
                    'duration' => '3 hari (24 JP) + uji kompetensi',
                    'summary' => 'Langkah awal karier keamanan siber: pemantauan keamanan, analisis log, dan respons insiden dasar, diakhiri uji kompetensi BNSP.',
                    'description' => "## Untuk Siapa?\n\nCocok bagi lulusan baru dan staf IT yang ingin beralih ke bidang keamanan siber.\n\n## Materi\n\n- Konsep CIA triad & ancaman umum\n- Hardening sistem operasi\n- Monitoring jaringan & analisis log\n- Prosedur respons insiden\n\nDitutup dengan uji kompetensi oleh LSP berlisensi BNSP.",
                    'benefits' => ['Sertifikat BNSP berlaku 3 tahun', 'Lab praktik virtual', 'Bank soal latihan uji kompetensi'],
                    'requirements' => ['Pendidikan minimal SMK/SMA jurusan terkait atau D3', 'Memahami dasar jaringan komputer'],
                    'audience' => ['Fresh graduate IT', 'Staf IT Support', 'Network Administrator'],
                    'price' => 4750000,
                    'promo_price' => 3950000,
                    'promo_until' => '2026-12-31',
                    'featured' => false,
                ],
                'batches' => [
                    ['code' => 'JCS-2611', 'start_date' => '2026-11-09', 'end_date' => '2026-11-12', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 35, 'status' => 'open'],
                    ['code' => 'JCS-2701', 'start_date' => '2027-01-11', 'end_date' => '2027-01-14', 'mode' => 'hybrid', 'location' => 'Yogyakarta', 'capacity' => 30, 'status' => 'open'],
                    ['code' => 'JCS-2703', 'start_date' => '2027-03-15', 'end_date' => '2027-03-18', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 35, 'status' => 'open'],
                ],
            ],
            [
                'slug' => 'analis-data-python-sql',
                'data' => [
                    'title' => 'Analis Data dengan Python & SQL',
                    'sku' => 'CRT-DA-01',
                    'category' => 'data-analitik',
                    'issuer' => 'internal',
                    'level' => 'Menengah',
                    'duration' => '4 hari (32 JP)',
                    'summary' => 'Dari query SQL hingga dashboard: olah, bersihkan, analisis, dan visualisasikan data bisnis dengan Python (pandas) dan SQL.',
                    'description' => "## Praktik Langsung\n\n80% sesi berupa praktik dengan dataset penjualan, keuangan, dan operasional perusahaan Indonesia.\n\n### Silabus\n\n- SQL: JOIN, agregasi, window function\n- Python: pandas, numpy, matplotlib\n- Data cleaning & exploratory data analysis\n- Menyusun insight dan presentasi untuk manajemen\n\nProyek akhir: dashboard analisis penjualan.",
                    'benefits' => ['Portofolio proyek analisis data', 'Akses rekaman sesi 6 bulan', 'Sertifikat Analis Data'],
                    'requirements' => ['Terbiasa dengan Excel', 'Laptop dengan RAM minimal 8 GB'],
                    'audience' => ['Business Analyst', 'Staf Keuangan/Operasional', 'Fresh graduate'],
                    'price' => 5500000,
                    'promo_price' => 4675000,
                    'promo_until' => '2026-10-31',
                    'featured' => true,
                ],
                'batches' => [
                    ['code' => 'DA-2611', 'start_date' => '2026-11-03', 'end_date' => '2026-11-06', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 40, 'status' => 'open'],
                    ['code' => 'DA-2612', 'start_date' => '2026-12-14', 'end_date' => '2026-12-17', 'mode' => 'offline', 'location' => 'Jakarta Barat', 'capacity' => 24, 'status' => 'open'],
                    ['code' => 'DA-2702', 'start_date' => '2027-02-22', 'end_date' => '2027-02-25', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 40, 'status' => 'open'],
                ],
            ],
            [
                'slug' => 'bnsp-associate-data-scientist',
                'data' => [
                    'title' => 'Sertifikasi BNSP Associate Data Scientist',
                    'sku' => 'BNSP-ADS-01',
                    'category' => 'data-analitik',
                    'issuer' => 'bnsp',
                    'level' => 'Menengah',
                    'duration' => '5 hari (40 JP) + uji kompetensi',
                    'summary' => 'Pelatihan machine learning terapan dan uji kompetensi skema Associate Data Scientist sesuai SKKNI bidang Kecerdasan Artifisial.',
                    'description' => "## Skema\n\nMengacu pada SKKNI Data Science. Unit kompetensi meliputi pengumpulan data, eksplorasi, persiapan data, pemodelan, evaluasi, dan deployment model sederhana.\n\nPeserta menyusun portofolio proyek machine learning sebagai bukti kompetensi.",
                    'benefits' => ['Sertifikat BNSP Associate Data Scientist', 'Mentoring proyek portofolio', 'Notebook & dataset latihan'],
                    'requirements' => ['Menguasai dasar Python', 'Memahami statistik dasar', 'Pendidikan minimal D3/S1'],
                    'audience' => ['Data Analyst', 'Software Engineer', 'Peneliti'],
                    'price' => 8750000,
                    'featured' => false,
                ],
                'batches' => [
                    ['code' => 'ADS-2612', 'start_date' => '2026-12-01', 'end_date' => '2026-12-05', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 30, 'status' => 'open'],
                    ['code' => 'ADS-2703', 'start_date' => '2027-03-01', 'end_date' => '2027-03-05', 'mode' => 'hybrid', 'location' => 'Jakarta Selatan', 'capacity' => 25, 'status' => 'open'],
                ],
            ],
            [
                'slug' => 'bnsp-supervisi-k3',
                'data' => [
                    'title' => 'Sertifikasi BNSP Supervisi K3 Lapangan',
                    'sku' => 'BNSP-K3-01',
                    'category' => 'k3',
                    'issuer' => 'bnsp',
                    'level' => 'Menengah',
                    'duration' => '3 hari (24 JP) + uji kompetensi',
                    'summary' => 'Kompetensi pengawasan K3 di lapangan: identifikasi bahaya, HIRADC, inspeksi, hingga investigasi kecelakaan kerja. Diuji oleh LSP berlisensi BNSP.',
                    'description' => "## Materi\n\n- Peraturan perundangan K3 di Indonesia\n- Identifikasi bahaya & penilaian risiko (HIRADC)\n- Inspeksi K3 dan *safety talk*\n- Investigasi kecelakaan & pelaporan\n- Alat Pelindung Diri (APD)\n\nUji kompetensi dilaksanakan pada hari terakhir.",
                    'benefits' => ['Sertifikat BNSP berlaku 3 tahun', 'Form inspeksi & HIRADC siap pakai', 'Kunjungan lapangan (kelas offline)'],
                    'requirements' => ['Pengalaman kerja di lapangan minimal 1 tahun', 'Sehat jasmani'],
                    'audience' => ['Supervisor Lapangan', 'Safety Officer', 'Mandor Konstruksi'],
                    'price' => 6500000,
                    'featured' => true,
                ],
                'batches' => [
                    ['code' => 'K3S-2611', 'start_date' => '2026-11-24', 'end_date' => '2026-11-27', 'mode' => 'offline', 'location' => 'Cikarang', 'capacity' => 30, 'status' => 'open'],
                    ['code' => 'K3S-2701', 'start_date' => '2027-01-26', 'end_date' => '2027-01-29', 'mode' => 'offline', 'location' => 'Balikpapan', 'capacity' => 30, 'status' => 'open', 'price_override' => 7250000],
                ],
            ],
            [
                'slug' => 'digital-marketing-strategist',
                'data' => [
                    'title' => 'Digital Marketing Strategist',
                    'sku' => 'CRT-DM-01',
                    'category' => 'digital-marketing',
                    'issuer' => 'internal',
                    'level' => 'Dasar',
                    'duration' => '3 hari (24 JP)',
                    'summary' => 'Susun strategi pemasaran digital yang terukur: riset audiens, SEO, iklan Meta & Google, email marketing, hingga membaca data kampanye.',
                    'description' => "## Yang Akan Dipelajari\n\n- Customer journey & persona\n- SEO on-page dan konten\n- Iklan berbayar (Meta Ads & Google Ads)\n- Marketing automation & email\n- Mengukur ROI dengan Google Analytics 4\n\nPeserta menyusun rencana pemasaran digital 90 hari untuk bisnisnya sendiri.",
                    'benefits' => ['Template rencana pemasaran 90 hari', 'Audit akun iklan gratis', 'Sertifikat Digital Marketing Strategist'],
                    'requirements' => ['Tidak ada persyaratan khusus'],
                    'audience' => ['Pemilik UMKM', 'Marketing Staff', 'Content Creator'],
                    'price' => 2950000,
                    'promo_price' => 2450000,
                    'promo_until' => '2026-09-30',
                    'featured' => false,
                ],
                'batches' => [
                    ['code' => 'DM-2611', 'start_date' => '2026-11-10', 'end_date' => '2026-11-12', 'mode' => 'online', 'location' => 'Zoom', 'capacity' => 50, 'status' => 'open'],
                    ['code' => 'DM-2702', 'start_date' => '2027-02-09', 'end_date' => '2027-02-11', 'mode' => 'offline', 'location' => 'Denpasar', 'capacity' => 25, 'status' => 'open'],
                ],
            ],
        ];
    }

    public function testimonials(): array
    {
        return [
            ['name' => 'Rina Kartikasari', 'role' => 'Project Manager', 'company' => 'PT Wahana Konstruksi Nusantara', 'quote' => 'Materi earned value dan manajemen risiko langsung saya terapkan di proyek gedung kantor kami. Laporan ke direksi jadi jauh lebih jelas.', 'program' => 'manajemen-proyek-profesional'],
            ['name' => 'Andi Pratama', 'role' => 'IT Security Lead', 'company' => 'Bank Daerah Sejahtera', 'quote' => 'Toolkit dokumen SMKI-nya sangat membantu. Enam bulan setelah pelatihan, kantor kami lolos audit sertifikasi ISO 27001.', 'program' => 'iso-27001-lead-implementer'],
            ['name' => 'Siti Nurhaliza Putri', 'role' => 'Data Analyst', 'company' => 'Startup Logistik Jakarta', 'quote' => 'Dari yang hanya bisa Excel, sekarang saya membuat dashboard penjualan sendiri dengan Python. Instrukturnya sabar dan praktis.', 'program' => 'analis-data-python-sql'],
            ['name' => 'Budi Santoso', 'role' => 'Safety Supervisor', 'company' => 'PT Mitra Energi Kaltim', 'quote' => 'Proses uji kompetensi BNSP-nya tertib dan pendampingan portofolionya detail. Sertifikat keluar tepat waktu.', 'program' => 'bnsp-supervisi-k3'],
            ['name' => 'Dewi Lestari', 'role' => 'Pemilik', 'company' => 'Batik Lestari Pekalongan', 'quote' => 'Setelah ikut kelas, biaya iklan saya turun hampir setengahnya tapi penjualan online justru naik.', 'program' => 'digital-marketing-strategist'],
        ];
    }

    public function faqs(): array
    {
        return [
            ['question' => 'Apakah harga yang tercantum sudah termasuk pajak?', 'answer' => 'Ya. Semua harga di website sudah **termasuk PPN**. Tidak ada biaya tambahan untuk materi dan sertifikat.'],
            ['question' => 'Apa beda sertifikat Internal dan BNSP?', 'answer' => "Sertifikat **Internal** diterbitkan lembaga kami sebagai bukti telah menyelesaikan pelatihan dan lulus evaluasi.\n\nSertifikat **BNSP** diterbitkan oleh Badan Nasional Sertifikasi Profesi melalui LSP berlisensi setelah Anda dinyatakan kompeten dalam uji kompetensi. Sertifikat ini diakui secara nasional."],
            ['question' => 'Bagaimana cara pembayaran?', 'answer' => 'Pembayaran dapat dilakukan melalui transfer bank, virtual account, kartu kredit, atau e-wallet. Untuk pendaftaran perusahaan tersedia invoice dengan termin pembayaran.'],
            ['question' => 'Apakah bisa mendaftarkan karyawan secara kolektif?', 'answer' => 'Bisa. Untuk pendaftaran 5 peserta atau lebih, hubungi tim kami melalui WhatsApp untuk penawaran harga korporat dan kelas in-house.'],
            ['question' => 'Bagaimana jika saya berhalangan hadir setelah membayar?', 'answer' => 'Anda dapat memindahkan jadwal ke batch berikutnya tanpa biaya paling lambat 7 hari sebelum kelas dimulai. Pengembalian dana mengikuti syarat & ketentuan yang berlaku.'],
            ['question' => 'Apakah kelas online mendapat sertifikat yang sama?', 'answer' => 'Ya. Peserta kelas online, offline, maupun hybrid memperoleh sertifikat yang sama selama memenuhi kehadiran minimal 90% dan lulus evaluasi.'],
            ['question' => 'Apakah program ini membantu persiapan ujian PMP?', 'answer' => 'Ya. Program memberikan 35 PDU (contact hours) yang menjadi syarat mendaftar ujian PMP dari PMI. Biaya ujian PMP tidak termasuk.', 'program' => 'manajemen-proyek-profesional'],
            ['question' => 'Berapa lama sertifikat BNSP terbit?', 'answer' => 'Sertifikat BNSP umumnya terbit 2–4 minggu setelah Anda dinyatakan kompeten oleh asesor.', 'program' => 'bnsp-manajer-proyek'],
            ['question' => 'Bagaimana jika saya belum kompeten saat uji?', 'answer' => 'Anda berhak mengikuti satu kali uji ulang tanpa biaya dalam waktu 6 bulan.', 'program' => 'bnsp-manajer-proyek'],
            ['question' => 'Apakah perlu menginstal software sebelum kelas?', 'answer' => 'Ya. Panduan instalasi Python (Anaconda) dan database latihan dikirim melalui email H-3 sebelum kelas.', 'program' => 'analis-data-python-sql'],
            ['question' => 'Apakah pelatihan ini menjamin kantor saya lolos audit ISO 27001?', 'answer' => 'Pelatihan membekali kompetensi implementasi. Kelulusan audit bergantung pada penerapan di organisasi Anda; kami menyediakan sesi konsultasi lanjutan secara terpisah.', 'program' => 'iso-27001-lead-implementer'],
        ];
    }

    public function site(): array
    {
        return [
            'name' => 'Sertifika',
            'tagline' => 'Sertifikasi profesional yang diakui industri',
            'hero_title' => 'Tingkatkan karier dengan sertifikasi yang diakui',
            'hero_subtitle' => 'Pelatihan dan uji kompetensi BNSP maupun sertifikasi internal di bidang manajemen proyek, keamanan informasi, data, K3, dan digital marketing.',
            'hero_cta_label' => 'Lihat Program',
            'stats' => [
                ['value' => '12.000+', 'label' => 'Alumni'],
                ['value' => '350+', 'label' => 'Perusahaan klien'],
                ['value' => '94%', 'label' => 'Tingkat kelulusan'],
                ['value' => '4,8/5', 'label' => 'Rating peserta'],
            ],
            'steps' => [
                ['title' => 'Pilih program', 'description' => 'Ikuti asesmen singkat atau jelajahi katalog untuk menemukan sertifikasi yang sesuai tujuan karier Anda.'],
                ['title' => 'Daftar & bayar', 'description' => 'Pilih batch dan metode belajar (online, offline, atau hybrid), lalu selesaikan pembayaran secara online.'],
                ['title' => 'Ikuti pelatihan', 'description' => 'Belajar bersama instruktur praktisi dengan studi kasus nyata dan materi yang bisa langsung diterapkan.'],
                ['title' => 'Raih sertifikat', 'description' => 'Lulus evaluasi atau uji kompetensi dan dapatkan sertifikat internal maupun BNSP.'],
            ],
            'contact_email' => 'halo@sertifika.id',
            'contact_phone' => '021-5091-2288',
            'contact_whatsapp' => '6281188002288',
            'contact_address' => "Gedung Graha Kompetensi Lt. 8\nJl. H.R. Rasuna Said Kav. 12, Kuningan\nJakarta Selatan 12940",
        ];
    }
}
