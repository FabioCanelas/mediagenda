<?php

namespace App\Filament\Widgets;

use App\Models\Alergia;
use App\Models\Paciente;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Aviso rojo con las alergias vigentes del paciente. Se muestra arriba de
 * todas las pantallas del paciente: ficha, historia clínica y prick test.
 */
class AvisoAlergias extends Widget
{
    protected static bool $isDiscovered = false;

    /**
     * Es un aviso de seguridad: se carga junto con la página, nunca después.
     */
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.aviso-alergias';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    public static function canView(): bool
    {
        return Auth::user()?->esDoctora() ?? false;
    }

    public function getPaciente(): ?Paciente
    {
        return match (true) {
            $this->record instanceof Paciente => $this->record,
            $this->record !== null && method_exists($this->record, 'paciente') => $this->record->paciente,
            default => null,
        };
    }

    /**
     * @return Collection<int, Alergia>
     */
    public function getAlergias(): Collection
    {
        return $this->getPaciente()?->alergiasVigentes()->orderByDesc('criticidad')->get() ?? new Collection;
    }
}
