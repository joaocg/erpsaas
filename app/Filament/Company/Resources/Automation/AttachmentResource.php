<?php

namespace App\Filament\Company\Resources\Automation;

use App\Filament\Company\Resources\Automation\AttachmentResource\Pages;
use App\Models\Attachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $navigationGroup = 'Automação & WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true),
                Forms\Components\Section::make('Anexo')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Arquivo')
                            ->disk('public')
                            ->directory('attachments')
                            ->preserveFilenames()
                            ->required()
                            ->afterStateUpdated(function (TemporaryUploadedFile|string|null $state, Set $set): void {
                                if (! $state instanceof TemporaryUploadedFile) {
                                    return;
                                }

                                $set('original_name', $state->getClientOriginalName());
                                $set('mime', $state->getMimeType());
                                $set('size', $state->getSize());

                                $set('raw_payload', json_encode([
                                    'uploaded_via' => 'filament',
                                    'original_name' => $state->getClientOriginalName(),
                                    'mime' => $state->getMimeType(),
                                    'size' => $state->getSize(),
                                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                            }),
                        Forms\Components\TextInput::make('original_name')
                            ->label('Nome original')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mime')
                            ->label('MIME')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('size')
                            ->label('Tamanho (bytes)')
                            ->numeric(),
                        Forms\Components\TextInput::make('source')
                            ->label('Origem')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('gemini_status')
                            ->label('Status Gemini')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('gemini_summary')
                            ->label('Resumo Gemini')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('gemini_topics')
                            ->label('Tópicos Gemini')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('gemini_amount')
                            ->label('Valor detectado')
                            ->numeric(),
                        Forms\Components\TextInput::make('gemini_currency')
                            ->label('Moeda detectada')
                            ->maxLength(3),
                        Forms\Components\TextInput::make('gemini_detected_type')
                            ->label('Tipo detectado')
                            ->maxLength(50),
                        Forms\Components\Textarea::make('raw_payload')
                            ->label('Payload bruto')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                            ->dehydrateStateUsing(function ($state) {
                                if (blank($state)) {
                                    return null;
                                }

                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);

                                    return $decoded ?? $state;
                                }

                                return $state;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gemini_status')
                    ->label('Status Gemini')
                    ->badge(),
                Tables\Columns\TextColumn::make('gemini_detected_type')
                    ->label('Detectado')
                    ->badge(),
                Tables\Columns\TextColumn::make('gemini_amount')
                    ->label('Valor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gemini_currency')
                    ->label('Moeda'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gemini_status')
                    ->label('Status Gemini')
                    ->options([
                        'pending' => 'Pendente',
                        'processed' => 'Processado',
                        'failed' => 'Falhou',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttachments::route('/'),
            'create' => Pages\CreateAttachment::route('/create'),
            'edit' => Pages\EditAttachment::route('/{record}/edit'),
        ];
    }
}
