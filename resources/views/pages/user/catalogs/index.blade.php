<?php

use App\Models\Room;
use App\Models\Cart;
use Carbon\Carbon;
use function Livewire\Volt\{state, uses, computed, rules};
use function Laravel\Folio\name;
use Jantinnerezo\LivewireAlert\LivewireAlert;

uses([LivewireAlert::class]);

name('catalogs.index');

state([
    'type' => 'daily',
    'check_in_date' => fn() => Carbon::parse(now())->format('Y-m-d'),
    'check_out_date',
])->url();

$rooms = computed(function () {
    return $this->getAvailableRooms($this->check_in_date, $this->check_out_date);
});

$getAvailableRooms = function ($checkInDate, $checkOutDate) {
    // Jika tidak ada tanggal check-in dan check-out, kembalikan semua kamar
    if (!$checkInDate && !$checkOutDate) {
        return Room::latest()->get();
    }

    // Jika hanya tanggal check-in yang diberikan
    if ($checkInDate && !$checkOutDate) {
        return Room::whereDoesntHave('items', function ($query) use ($checkInDate) {
            $query->where('check_out_date', '>', $checkInDate);
        })
            ->latest()
            ->get();
    }

    // Jika hanya tanggal check-out yang diberikan
    if (!$checkInDate && $checkOutDate) {
        return Room::whereDoesntHave('items', function ($query) use ($checkOutDate) {
            $query->where('check_in_date', '<', $checkOutDate);
        })
            ->latest()
            ->get();
    }

    // Jika kedua tanggal diberikan
    if ($checkInDate && $checkOutDate) {
        return Room::whereDoesntHave('items', function ($query) use ($checkInDate, $checkOutDate) {
            $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                $q->where('check_in_date', '<', $checkOutDate)->where('check_out_date', '>', $checkInDate);
            });
        })
            ->latest()
            ->get();
    }
};

state([
    'cart' => fn() => Cart::where('user_id', Auth()->id())->get(),
    'user_id' => fn() => Auth()->user()->id ?? '',
    'booking_date' => fn() => Carbon::now()->format('Y-m-d'),
]);

$addToCart = function ($room) {
    // Debugging untuk melihat data yang masuk
    // dd($this->all()); // Jangan lupa hapus jika sudah tidak diperlukan

    if (!Auth::check()) {
        return $this->redirect('/login');
    }

    if (!$room || !isset($room['id'])) {
        $this->alert('warning', 'Silakan pilih kamar terlebih dahulu!', [
            'position' => 'center',
            'timer' => 2000,
            'toast' => true,
        ]);
        return;
    }

    if (!isset($room['daily_price']) || !isset($room['monthly_price'])) {
        $this->alert('warning', 'Harga kamar tidak valid!', [
            'position' => 'center',
            'timer' => 2000,
            'toast' => true,
        ]);
        return;
    }

    try {
        $this->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $checkCart = Cart::where('user_id', Auth::id())->where('room_id', $room['id'])->exists();

        if ($checkCart) {
            $this->alert('warning', 'Kamar sudah ada di keranjang!', [
                'position' => 'center',
                'timer' => 2000,
                'toast' => true,
            ]);
            return;
        }

        $checkInDate = \Carbon\Carbon::parse($this->check_in_date);
        $checkOutDate = \Carbon\Carbon::parse($this->check_out_date);

        // Menghitung selisih hari antara check-in dan check-out
        $days = $checkInDate->diffInDays($checkOutDate);

        // Menghitung harga berdasarkan tipe
        if ($this->type === 'daily') {
            $price = $room['daily_price'] * $days;
        } else {
            $months = ceil($days / 30); // Anggap 1 bulan = 30 hari
            $price = $room['monthly_price'] * $months;
        }

        Cart::create([
            'user_id' => Auth::id(),
            'room_id' => $room['id'],
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'type' => $this->type,
            'price' => $price,
        ]);

        $this->dispatch('cart-updated');

        $this->alert('success', 'Kamar berhasil ditambahkan ke keranjang!', [
            'position' => 'center',
            'timer' => 2000,
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    } catch (\Throwable $th) {
        $errorMessages = implode('<br>', $th->validator->errors()->all());

        $this->alert('error', 'Proses gagal! ' . '<br>' . $errorMessages, [
            'position' => 'center',
            'timer' => 4000,
            'toast' => true,
            'timerProgressBar' => true,
        ]);
        $this->redirect('#form-input');
    }
};

?>

<x-guest-layout>
    <x-slot name="title">Katalog Kamar</x-slot>

    @volt
        <div class="container">
            @foreach ($errors->all() as $item)
                <p>{{ $item }}</p>
            @endforeach

            <section class="py-5" id="form-input">
                <div class="container-fluid mb-5">
                    <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s"
                        style="max-width: 800px; visi
                    bility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
                        <h1 class="display-4 mb-4">Pemesanan Kamar Kost</h1>
                        <p class="mb-0">
                            Selamat datang di layanan pemesanan kamar kost kami! Nikmati kenyamanan dan fasilitas terbaik
                            yang kami tawarkan. Kami
                            menyediakan berbagai pilihan kamar yang sesuai dengan kebutuhan Anda.
                        </p>
                    </div>

                    <div class="mt-4 row bg-light px-2 py-5 rounded-3 wow fadeInUp" data-wow-dela y="0.2s">
                        <div class="mb-4 col-md">
                            <label for="check_in_date" class="form-label">Tanggal Check-In</label>
                            <input wire:model.live="check_in_date" type="date" class="form-control" id="check_in_date"
                                min="{{ now()->format('Y-m-d') }}">
                            @error('check_in_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-4 col-md">
                            <label for="check_out_date" class="form-label">Tanggal Check-Out</label>
                            <input wire:model.live="check_out_date" type="date" class="form-control" id="check_out_date"
                                min="{{ Carbon::parse($check_in_date)->format('Y-m-d') }}">
                            @error('check_out_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Tipe Pemesanan</label>
                            <select class="form-select" name="type" id="type" wire:model.live='type'>
                                <option value="daily" selected>Perhari</option>
                                <option value="monthly">Perbulan</option>
                            </select>
                            @error('type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-3 text-center">
                            <span class="text-center">
                                <div class="d-none spinner-border" wire:loading.class.remove="d-none" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </span>
                        </div>
                    </div>

                    <div class="row g-4 justify-content-center py-5">
                        @foreach ($this->rooms as $room)
                            <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.2s"
                                style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
                                <div class="blog-item">
                                    <div class=" blog-img">
                                        <img src="{{ Storage::url($room->images->first()->image_path) }}"
                                            class="img-fluid rounded-top w-100" alt="image room">

                                    </div>
                                    <div class="blog-content p-4">
                                        <h4 class=" mb-3 fw-bold">
                                            Kamar {{ $room->number }}
                                        </h4>
                                        <h5 class="mb-3 fw-bold">
                                            {{ $type == 'monthly' ? formatRupiah($room->monthly_price) : formatRupiah($room->daily_price) }}
                                        </h5>
                                        <p class="mb-3">
                                            @foreach ($room->facilities as $facility)
                                                {{ $facility->name }},
                                            @endforeach
                                        </p>
                                        <a wire:click.prevent="addToCart({{ $room }})"
                                            class="btn btn-primary w-100">
                                            Pesan Kamar
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>

                </div>
            </section>
        </div>
    @endvolt
</x-guest-layout>
