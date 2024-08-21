<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Filament\Resources\BookingTransactionResource\RelationManagers;
use App\Http\Controllers\Api\BookingTransactionController;
use App\Models\BookingTransaction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\WhatsappNotificationTrait;

class BookingTransactionResource extends Resource
{
    use WhatsappNotificationTrait;
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('booking_trx_id')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone_number')
                    ->required()
                    ->maxLength(255),

                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('IDR'),

                TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->prefix('Days'),

                DatePicker::make('started_at')
                    ->required(),

                DatePicker::make('ended_at')
                    ->required(),

                Select::make('is_paid')
                    ->options([
                        true => 'Paid',
                        false => 'No Paid',
                    ])
                    ->required(),

                Select::make('office_space_id')
                    ->relationship('officeSpace', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_trx_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('officeSpace.name'),

                Tables\Columns\TextColumn::make('started_at')
                    ->date(),

                Tables\Columns\BooleanColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Is Paid?'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Approve')
                    ->label('Approve')
                    ->action(function (BookingTransaction $record) {
                        $controller = app(BookingTransactionController::class);
                        $controller->approveBooking($record);
                        // Set 'is_paid' to true
                        // $record->is_paid = true;
                        // $record->save();

                        // // Send success notification
                        // Notification::make()
                        //     ->title('Booking Approved')
                        //     ->success()
                        //     ->body('Booking has been approved successfully')
                        //     ->send();

                        // // Send WhatsApp message notification
                        // $phone = $record->phone_number; // Use $record instead of $bookingTransaction
                        // $messageBody = "Hi {$record->name}, Pemesanan Anda dengan kode {$record->booking_trx_id} sudah terbayar penuh.\n\n";
                        // $messageBody .= "Silahkan datang kepada lokasi kantor {$record->officeSpace->name} untuk mulai menggunakan ruangan kerja tersebut.\n\n";
                        // $messageBody .= "Jika anda memiliki pertanyaan silahkan menghubungi CS Kami di wa.me/6285216000521.";

                        // // Assuming sendTextWatsapp is a defined method in the current class
                        // $this->sendTextWatsapp($phone, $messageBody);
                    })
                    ->color('success') // Fixed typo: 'succes' to 'success'
                    ->requiresConfirmation()
                    ->visible(fn(BookingTransaction $record) => !$record->is_paid),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingTransactions::route('/'),
            'create' => Pages\CreateBookingTransaction::route('/create'),
            'edit' => Pages\EditBookingTransaction::route('/{record}/edit'),
        ];
    }
}
