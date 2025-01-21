<?php

use App\Models\Room;
use App\Models\Setting;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use function Laravel\Folio\name;
use function Livewire\Volt\{computed, state, usesPagination, uses, rules};

uses([LivewireAlert::class]);

name('rooms.index');

state(['search'])->url();

usesPagination(theme: 'bootstrap');

$rooms = computed(function () {
    if ($this->search == null) {
        return Room::query()
            ->latest()
            ->paginate(10);
    } else {
        return Room::query()
            ->where(function ($query) {
                $query->whereAny(['number', 'room_status'], 'LIKE', "%{$this->search}%");
            })
            ->latest()
            ->paginate(10);
    }
});

$destroy = function (room $room) {
    try {
        foreach ($room->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $room->images()->delete();
        $room->delete();
        $this->alert('success', 'Proses berhasil!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
    } catch (\Throwable $th) {
        $this->alert('error', 'Proses gagal!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
    }
};

?>

<x-admin-layout>
    <div>
        <x-slot name="title">Data kamar</x-slot>

        @volt
        <div>

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <a href="{{ route('rooms.create') }}" class="btn btn-primary">Tambah
                                Kamar</a>
                        </div>
                        <div class="col">
                            <input wire:model.live="search" type="search" class="form-control" name="search" id="search"
                                aria-describedby="searchId" placeholder="Masukkan kata kunci pencarian" />
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive border rounded">
                        <table class="table table-striped text-center text-nowrap">
                            <thead>
                                <tr>
                                    <th>No. Kamar</th>
                                    <th>Status</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->rooms as $no => $item)
                                    <tr>
                                        <td>
                                            Kamar
                                            {{ $item->number }}
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">
                                                {{ __('room.' . $item->room_status) }}
                                            </button>
                                        </td>
                                        <td>
                                            <div>
                                                <a href="{{ route('rooms.edit', ['room' => $item->id]) }}"
                                                    class="btn btn-sm btn-warning">Edit</a>
                                                <button wire:loading.attr='disabled' wire:click='destroy({{ $item->id }})'
                                                    wire:confirm="Apakah kamu yakin ingin menghapus data ini?"
                                                    class="btn btn-sm btn-danger">
                                                    {{ __('Hapus') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>

                        <div class="container">
                            {{ $this->rooms->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endvolt

    </div>
</x-admin-layout>
