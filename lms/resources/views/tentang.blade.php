<x-layout title="Tentang Kelompok — KampusLMS">
    <div class="container-medium" style="margin: 0 auto;">
        <div class="page-header">
            <h1 class="page-title">Tentang Kelompok 06 (Kelas A)</h1>
            <p class="page-desc">Proyek <strong>KampusLMS</strong> dikerjakan oleh:</p>
        </div>

        <div class="card">
            <div class="item-list">
                @foreach ([
                    'Muhammad Farraz Kanz' => '10241047',
                    'Muhammad Ikhsyan' => '10241049',
                    'Muhammad Syahid Ridho' => '10241051',
                    'Muhammad Yusuf Pratama' => '10241053',
                ] as $member => $nim)
                    <div class="item-card" style="padding: 14px 18px;">
                        <div class="item-main">
                            <h3 class="item-title">{{ $member }}</h3>
                        </div>
                        <span class="badge badge-neutral">NIM {{ $nim }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layout>
