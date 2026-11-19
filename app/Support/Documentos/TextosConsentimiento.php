<?php

namespace App\Support\Documentos;

use App\Enums\ProcedimientoConsentimiento;

/**
 * Textos de ejemplo del consentimiento informado, con los apartados que pide la
 * NOM-004 (acto autorizado, riesgos y beneficios, autorización para urgencias).
 * La doctora debe revisarlos antes de usarlos con pacientes reales.
 */
class TextosConsentimiento
{
    /**
     * @return array{descripcion: string, beneficios: array<int, string>, riesgos: array<int, string>, cuidados: string, alternativas: string}
     */
    public static function para(ProcedimientoConsentimiento $procedimiento): array
    {
        return match ($procedimiento) {
            ProcedimientoConsentimiento::PrickTest => [
                'descripcion' => 'Se colocan gotas de extractos de alérgenos sobre la piel del antebrazo y se hace una punción superficial con una lanceta. Entre 15 y 20 minutos después se mide la reacción de la piel.',
                'beneficios' => [
                    'Identificar las sustancias a las que el paciente es alérgico.',
                    'Orientar las medidas para evitarlas y el tratamiento, incluida la inmunoterapia.',
                ],
                'riesgos' => [
                    'Picazón, enrojecimiento y ronchas en la zona de la prueba, que desaparecen en pocas horas.',
                    'Con poca frecuencia, una reacción más extensa en el brazo.',
                    'Muy rara vez, una reacción generalizada (urticaria, dificultad para respirar o anafilaxia) que requiere tratamiento inmediato.',
                ],
                'cuidados' => 'Suspender los antihistamínicos el tiempo que indique la doctora antes de la prueba.',
                'alternativas' => 'Análisis de IgE específica en sangre.',
            ],
            ProcedimientoConsentimiento::Inmunoterapia => [
                'descripcion' => 'Se aplican inyecciones subcutáneas de extractos de alérgenos, primero en dosis crecientes (fase de inicio) y después en una dosis de mantenimiento periódica, durante 3 a 5 años. Después de cada aplicación el paciente permanece 30 minutos en observación en el consultorio.',
                'beneficios' => [
                    'Disminuir los síntomas y la necesidad de medicamentos.',
                    'Puede modificar la evolución de la enfermedad alérgica y prevenir nuevas sensibilizaciones.',
                ],
                'riesgos' => [
                    'Reacciones en el sitio de la inyección: enrojecimiento, hinchazón o picazón.',
                    'Reacciones generalizadas: urticaria, rinitis, asma o, rara vez, anafilaxia, que puede ser grave. Por eso la observación de 30 minutos es obligatoria.',
                    'Las reacciones son más probables con asma no controlada, fiebre o si se atrasan las dosis.',
                ],
                'cuidados' => 'Avisar antes de cada dosis si tuvo síntomas, fiebre, si cambió de medicamentos o si está embarazada, y cumplir las fechas indicadas.',
                'alternativas' => 'Tratamiento con medicamentos y medidas para evitar los alérgenos.',
            ],
        };
    }
}
