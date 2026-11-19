<?php

namespace App\Filament\Resources\Citas\Pages;

use App\Filament\Resources\Citas\CitaResource;
use App\Models\Cita;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * HU-14: agenda de lunes a sábado. Las citas canceladas no se muestran.
 */
class AgendaSemanal extends Page
{
    protected static string $resource = CitaResource::class;

    protected string $view = 'filament.citas.agenda-semanal';

    protected static ?string $title = 'Agenda semanal';

    #[Url]
    public string $semana = '';

    public function mount(): void
    {
        $this->semana = $this->lunes($this->semana ?: now()->toDateString())->toDateString();
    }

    public function getSubheading(): string
    {
        $lunes = CarbonImmutable::parse($this->semana);

        return 'Del '.$lunes->format('d/m').' al '.$lunes->addDays(5)->format('d/m/Y');
    }

    /**
     * @return Collection<int, array{fecha: CarbonImmutable, citas: Collection<int, Cita>}>
     */
    public function getDias(): Collection
    {
        $lunes = CarbonImmutable::parse($this->semana);

        $citas = Cita::query()
            ->with('paciente')
            ->where('estado', '!=', 'cancelada')
            ->whereBetween('inicio', [$lunes->startOfDay(), $lunes->addDays(5)->endOfDay()])
            ->orderBy('inicio')
            ->get()
            ->groupBy(fn (Cita $cita): string => $cita->inicio->toDateString());

        return collect(range(0, 5))->map(fn (int $dia): array => [
            'fecha' => $lunes->addDays($dia),
            'citas' => $citas->get($lunes->addDays($dia)->toDateString(), collect()),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anterior')
                ->label('Semana anterior')
                ->icon(Heroicon::OutlinedChevronLeft)
                ->color('gray')
                ->action(fn () => $this->semana = CarbonImmutable::parse($this->semana)->subWeek()->toDateString()),
            Action::make('hoy')
                ->label('Esta semana')
                ->color('gray')
                ->action(fn () => $this->semana = $this->lunes(now()->toDateString())->toDateString()),
            Action::make('siguiente')
                ->label('Semana siguiente')
                ->icon(Heroicon::OutlinedChevronRight)
                ->iconPosition('after')
                ->color('gray')
                ->action(fn () => $this->semana = CarbonImmutable::parse($this->semana)->addWeek()->toDateString()),
            Action::make('nueva')
                ->label('Nueva cita')
                ->url(CitaResource::getUrl('create')),
        ];
    }

    private function lunes(string $fecha): CarbonImmutable
    {
        return CarbonImmutable::parse($fecha)->startOfWeek(CarbonImmutable::MONDAY);
    }
}
