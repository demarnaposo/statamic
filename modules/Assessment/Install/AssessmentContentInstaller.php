<?php

namespace Modules\Assessment\Install;

use Illuminate\Console\OutputStyle;
use Modules\Catalog\Contracts\ContentInstaller;
use Statamic\Facades\Blueprint;
use Statamic\Facades\GlobalSet;

final class AssessmentContentInstaller implements ContentInstaller
{
    public function installStructure(OutputStyle $output, bool $force = false): void
    {
        if (! GlobalSet::findByHandle('assessment')) {
            GlobalSet::make('assessment')->title('Asesmen Minat')->save();
            $output->writeln('  • Global set <info>assessment</info> dibuat');
        }

        $existing = Blueprint::find('globals.assessment');
        if ($existing && ! $force) {
            return;
        }

        ($existing ?? Blueprint::make('assessment')->setNamespace('globals'))
            ->setContents($this->blueprint())
            ->save();

        $output->writeln('  • Blueprint <info>globals.assessment</info> '.($existing ? 'diperbarui' : 'dibuat'));
    }

    public function installDemo(OutputStyle $output, bool $force = false): void
    {
        $vars = GlobalSet::findByHandle('assessment')->inDefaultSite();

        if (! $force && filled($vars->get('questions'))) {
            return;
        }

        $vars->data($this->demo())->save();
        $output->writeln('  • Global "assessment" diisi ('.count($this->demo()['questions']).' pertanyaan)');
    }

    private function field(string $handle, string $type, string $display, array $config = []): array
    {
        return ['handle' => $handle, 'field' => array_merge(['type' => $type, 'display' => $display], $config)];
    }

    private function blueprint(): array
    {
        $idRule = ['required', 'alpha_dash', 'max:40'];

        return [
            'title' => 'Asesmen Minat',
            'tabs' => [
                'main' => [
                    'display' => 'Asesmen',
                    'sections' => [
                        [
                            'display' => 'Pengantar',
                            'instructions' => 'Kuis singkat untuk merekomendasikan kategori program. Setiap pilihan jawaban menambah 1 poin untuk kategorinya.',
                            'fields' => [
                                $this->field('title', 'text', 'Judul', ['validate' => ['required']]),
                                $this->field('intro', 'textarea', 'Pengantar'),
                                $this->field('version', 'integer', 'Versi', [
                                    'default' => 1,
                                    'validate' => ['required', 'integer', 'min:1'],
                                    'instructions' => 'Naikkan angka ini setiap kali pertanyaan atau pilihan diubah, agar jawaban lama dapat dibedakan.',
                                ]),
                            ],
                        ],
                        [
                            'display' => 'Pertanyaan',
                            'fields' => [
                                $this->field('questions', 'grid', 'Pertanyaan', [
                                    'mode' => 'stacked',
                                    'add_row' => 'Tambah Pertanyaan',
                                    'min_rows' => 1,
                                    'fields' => [
                                        $this->field('question_id', 'text', 'ID Pertanyaan', ['validate' => $idRule, 'width' => 33, 'instructions' => 'Unik, huruf kecil tanpa spasi, mis. `q1`. Jangan diubah setelah tayang.']),
                                        $this->field('question', 'text', 'Pertanyaan', ['validate' => ['required'], 'width' => 66]),
                                        $this->field('options', 'grid', 'Pilihan Jawaban', [
                                            'mode' => 'table',
                                            'add_row' => 'Tambah Pilihan',
                                            'min_rows' => 2,
                                            'fields' => [
                                                $this->field('option_id', 'text', 'ID', ['validate' => $idRule]),
                                                $this->field('label', 'text', 'Label', ['validate' => ['required']]),
                                                $this->field('category', 'terms', 'Kategori', [
                                                    'taxonomies' => ['categories'],
                                                    'max_items' => 1,
                                                    'mode' => 'select',
                                                    'create' => false,
                                                    'validate' => ['required'],
                                                ]),
                                            ],
                                        ]),
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function demo(): array
    {
        $map = ['a' => 'manajemen-proyek', 'b' => 'keamanan-informasi', 'c' => 'data-analitik', 'd' => 'k3', 'e' => 'digital-marketing'];

        $questions = [
            'q1' => ['Bagian pekerjaan mana yang paling Anda nikmati?', [
                'a' => 'Mengatur jadwal, anggaran, dan tim agar target tercapai',
                'b' => 'Memastikan sistem dan data perusahaan aman',
                'c' => 'Menggali angka untuk menemukan pola dan insight',
                'd' => 'Memastikan semua orang bekerja dengan selamat',
                'e' => 'Membuat kampanye yang menarik pelanggan baru',
            ]],
            'q2' => ['Masalah apa yang paling sering Anda hadapi di kantor?', [
                'a' => 'Proyek molor dan biaya membengkak',
                'b' => 'Kekhawatiran kebocoran data atau serangan siber',
                'c' => 'Keputusan dibuat tanpa data yang jelas',
                'd' => 'Insiden atau hampir-celaka di lapangan',
                'e' => 'Penjualan online stagnan',
            ]],
            'q3' => ['Alat kerja mana yang paling ingin Anda kuasai?', [
                'a' => 'Gantt chart dan software manajemen proyek',
                'b' => 'Firewall, SIEM, dan kerangka ISO 27001',
                'c' => 'SQL, Python, dan dashboard BI',
                'd' => 'Form inspeksi, HIRADC, dan APD',
                'e' => 'Meta Ads, Google Ads, dan Google Analytics',
            ]],
            'q4' => ['Di lingkungan kerja seperti apa Anda paling nyaman?', [
                'a' => 'Rapat koordinasi lintas divisi',
                'b' => 'Ruang server dan pusat operasi keamanan',
                'c' => 'Di depan laptop mengolah data',
                'd' => 'Langsung di lapangan, pabrik, atau proyek',
                'e' => 'Tim kreatif yang dinamis',
            ]],
            'q5' => ['Pencapaian mana yang paling membanggakan bagi Anda?', [
                'a' => 'Proyek selesai tepat waktu dan sesuai anggaran',
                'b' => 'Organisasi lolos audit keamanan',
                'c' => 'Analisis saya mengubah keputusan manajemen',
                'd' => 'Nol kecelakaan kerja selama setahun',
                'e' => 'Kampanye viral dengan biaya iklan rendah',
            ]],
            'q6' => ['Peran apa yang Anda incar dalam 3 tahun ke depan?', [
                'a' => 'Project Manager atau Kepala PMO',
                'b' => 'Security Analyst atau CISO',
                'c' => 'Data Analyst atau Data Scientist',
                'd' => 'Safety Officer atau Manajer HSE',
                'e' => 'Digital Marketing Manager',
            ]],
            'q7' => ['Topik artikel mana yang paling mungkin Anda klik?', [
                'a' => '7 teknik agar proyek tidak molor',
                'b' => 'Modus phishing terbaru yang menyasar perusahaan',
                'c' => 'Cara membaca tren penjualan dengan Python',
                'd' => 'Regulasi K3 terbaru untuk industri',
                'e' => 'Strategi konten TikTok untuk bisnis',
            ]],
        ];

        $rows = [];
        foreach ($questions as $id => [$text, $options]) {
            $rows[] = [
                'question_id' => $id,
                'question' => $text,
                'options' => array_map(fn ($key, $label) => [
                    'option_id' => $id.$key,
                    'label' => $label,
                    'category' => $map[$key],
                ], array_keys($options), $options),
            ];
        }

        return [
            'title' => 'Temukan Sertifikasi yang Cocok untuk Anda',
            'intro' => 'Jawab 7 pertanyaan singkat (sekitar 2 menit). Kami akan merekomendasikan bidang sertifikasi yang paling sesuai dengan minat dan tujuan karier Anda.',
            'version' => 1,
            'questions' => $rows,
        ];
    }
}
