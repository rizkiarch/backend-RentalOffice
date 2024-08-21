<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingTransactionRequest;
use App\Http\Resources\Api\BookingTransactionResource;
use App\Http\Resources\Api\ViewBookingResource;
use App\Models\BookingTransaction;
use App\Models\OfficeSpace;
use App\Traits\WhatsappNotificationTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class BookingTransactionController extends Controller
{
    use WhatsappNotificationTrait;

    public function booking_details(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'booking_trx_id' => 'required|string'
        ]);

        $booking = BookingTransaction::where('phone_number', $request->phone_number)
            ->where('booking_trx_id', $request->booking_trx_id)
            ->with(['officeSpace', 'officeSpace.city'])
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }
        return new ViewBookingResource($booking);
    }
    public function store(StoreBookingTransactionRequest $request)
    {
        $validated = $request->validated();

        $officeSpace = OfficeSpace::find($validated['office_space_id']);

        $validated['is_paid'] = false;
        $validated['booking_trx_id'] = BookingTransaction::generateUniqueTrxId();
        $validated['duration'] = $officeSpace->duration;

        $validated['ended_at'] = (new \DateTime($validated['started_at']))
            ->modify("+{$officeSpace->duration} days")
            ->format('Y-m-d H:i:s');

        $bookingTransaction = BookingTransaction::create($validated);

        $phone = $bookingTransaction->phone_number;

        // message notification whatsapp
        $messageBody = "Hi {$bookingTransaction->name}, Terima Kasih telah booking kantor di FirstOffice.\n\n";
        $messageBody .= "Pesanan Kantor {$bookingTransaction->officeSpace->name} Anda sedang kami proses dengan Booking TRX ID: {$bookingTransaction->booking_trx_id}.\n\n";
        $messageBody .= "Kami akan menginformasikan kembali status pemesanan Anda secepat mungkin.";

        $this->sendTextWatsapp($phone, $messageBody);
        // callback
        $bookingTransaction->load('officeSpace');
        return new BookingTransactionResource($bookingTransaction);
    }

    public function approveBooking(BookingTransaction $record)
    {
        $record->is_paid = true;
        $record->save();

        Notification::make()
            ->title('Booking Approved')
            ->success()
            ->body('Booking has been approved successfully')
            ->send();

        $phone = $record->phone_number;
        $messageBody = "Hi {$record->name}, Pemesanan Anda dengan kode {$record->booking_trx_id} sudah terbayar penuh.\n\n";
        $messageBody .= "Silahkan datang kepada lokasi kantor {$record->officeSpace->name} untuk mulai menggunakan ruangan kerja tersebut.\n\n";
        $messageBody .= "Jika anda memiliki pertanyaan silahkan menghubungi CS Kami di wa.me/6285216000521.";

        $this->sendTextWatsapp($phone, $messageBody);
    }
}
