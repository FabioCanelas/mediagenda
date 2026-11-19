<?php

namespace Database\Factories;

use App\Models\HistoriaClinica;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoriaClinica>
 */
class HistoriaClinicaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'padecimiento_actual' => fake()->randomElement([
                'Estornudos en salvas, rinorrea hialina y prurito nasal de predominio matutino desde hace 2 años. Empeora al limpiar la casa.',
                'Tos seca nocturna y sibilancias con el ejercicio desde hace 6 meses. Mejora con salbutamol.',
                'Ronchas pruriginosas generalizadas de menos de 24 horas de duración, recurrentes desde hace 3 meses.',
                'Lesiones eccematosas en pliegues de codos y rodillas desde la infancia, con prurito intenso.',
                'Obstrucción nasal y lagrimeo en primavera, con prurito ocular.',
            ]),
            'antecedentes_heredofamiliares' => fake()->randomElement([
                'Madre con rinitis alérgica. Padre asmático.',
                'Hermano con dermatitis atópica.',
                'Sin antecedentes de atopia en la familia.',
            ]),
            'antecedentes_personales_patologicos' => fake()->randomElement([
                'Dermatitis atópica en la infancia. Niega cirugías. No fuma.',
                'Asma diagnosticada a los 8 años. Usa salbutamol a demanda. No fuma.',
                'Niega enfermedades crónicas. Tabaquismo negado.',
            ]),
            'antecedentes_personales_no_patologicos' => fake()->randomElement([
                'Vive en casa con alfombras y peluches. Tiene un gato.',
                'Departamento con humedad en paredes. Sin mascotas.',
                'Casa de adobe, convive con dos perros.',
            ]),
            'interrogatorio_aparatos_sistemas' => 'Respiratorio y piel según padecimiento actual. Resto sin datos patológicos.',
            'habitus_exterior' => 'Consciente, orientado, hidratado, sin dificultad respiratoria.',
            'presion_sistolica' => fake()->numberBetween(100, 130),
            'presion_diastolica' => fake()->numberBetween(60, 85),
            'frecuencia_cardiaca' => fake()->numberBetween(60, 95),
            'frecuencia_respiratoria' => fake()->numberBetween(14, 20),
            'temperatura' => fake()->randomFloat(1, 36.0, 37.2),
            'saturacion_oxigeno' => fake()->numberBetween(90, 98),
            'peso_kg' => fake()->randomFloat(2, 45, 95),
            'talla_cm' => fake()->randomFloat(1, 145, 185),
            'exploracion_regional' => 'Cornetes pálidos e hipertróficos. Pliegue nasal transverso. Campos pulmonares sin agregados.',
            'pronostico' => 'Bueno para la función con tratamiento y control ambiental.',
            'indicacion_terapeutica' => 'Medidas de control de ácaros. Antihistamínico oral cada 24 horas por 30 días. Control en 4 semanas.',
        ];
    }
}
