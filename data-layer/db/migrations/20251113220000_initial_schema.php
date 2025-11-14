
<?php

use Phinx\Migration\AbstractMigration;

class InitialSchema extends AbstractMigration
{
    public function up()
    {
        // Leer el archivo schema.sql y ejecutarlo
        $sql = file_get_contents(__DIR__ . '/../../sql/schema.sql');
        
        // Dividir por sentencias (punto y coma + salto de línea)
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );
        
        // Ejecutar cada sentencia
        foreach ($statements as $statement) {
            $this->execute($statement);
        }
    }

    public function down()
    {
        // Eliminar todas las tablas (en orden inverso por FK)
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        
        $tables = [
            'reservation_participants',
            'match_details',
            'reservations',
            'manager_shifts',
            'championship_teams',
            'championships',
            'team_memberships',
            'teams',
            'field_schedule_exceptions',
            'field_operating_hours',
            'fields',
            'users',
            'athlete_states',
            'user_states',
            'roles'
        ];
        
        foreach ($tables as $table) {
            $this->execute("DROP TABLE IF EXISTS `$table`");
        }
        
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}