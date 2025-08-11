<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;

/**
 * Abstract base class for listing models
 *
 * This class provides a common interface for models that handle
 * data listing and searching functionality.
 *
 * @author Sistema SAM
 * @since 2.0
 */
abstract class Listado extends ActiveRecord
{
    // Scenario constants
    const SCENARIO_BUSCAR = 'listadoBuscar';
    const SCENARIO_EXPORTAR = 'listadoExportar';
    
    // Default permission for listings
    const DEFAULT_PERMISSION = 6500;
    
    // Pagination defaults
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;
    
    /**
     * Performs the search in the database and returns the results
     *
     * @return ActiveDataProvider
     */
    abstract public function buscar(): ActiveDataProvider;
    
    /**
     * Validates the search parameters
     *
     * @return bool
     */
    abstract public function validar(): bool;
    
    /**
     * Returns the primary key fields of the model
     *
     * @return array
     */
    abstract public function pk(): array;
    
    /**
     * Returns the sort configuration for the listing
     *
     * @return array
     */
    public function sort(): array
    {
        return [];
    }
    
    /**
     * Returns the title for the listing
     *
     * @return string
     */
    public function titulo(): string
    {
        return 'Listado';
    }
    
    /**
     * Returns the permission required to access this listing
     *
     * @return int
     */
    public function permiso(): int
    {
        return self::DEFAULT_PERMISSION;
    }
    
    /**
     * Returns the scenario to use for validating data before searching
     *
     * @return string
     */
    public function scenarioBuscar(): string
    {
        return self::SCENARIO_BUSCAR;
    }
    
    /**
     * Returns the scenario to use for exporting data
     *
     * @return string
     */
    public function scenarioExportar(): string
    {
        return self::SCENARIO_EXPORTAR;
    }
    
    /**
     * Returns the page size for pagination
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return self::DEFAULT_PAGE_SIZE;
    }
    
    /**
     * Returns the maximum allowed page size
     *
     * @return int
     */
    public function getMaxPageSize(): int
    {
        return self::MAX_PAGE_SIZE;
    }
    
    /**
     * Sanitizes search input to prevent XSS
     *
     * @param string $input
     * @return string
     */
    protected function sanitizeInput(string $input): string
    {
        return Html::encode(trim($input));
    }
    
    /**
     * Validates that the user has permission to access this listing
     *
     * @return bool
     */
    public function validatePermission(): bool
    {
        if (\Yii::\$app->user->isGuest) {
            return false;
        }
        
        // Check if user has the required permission
        \$permisos = \Yii::\$app->session->get('permisos', []);
        return in_array(\$this->permiso(), \$permisos, true);
    }
    
    /**
     * Returns common scenarios for listing models
     *
     * @return array
     */
    public function scenarios(): array
    {
        \$scenarios = parent::scenarios();
        \$scenarios[self::SCENARIO_BUSCAR] = \$scenarios[self::SCENARIO_DEFAULT];
        \$scenarios[self::SCENARIO_EXPORTAR] = \$scenarios[self::SCENARIO_DEFAULT];
        
        return \$scenarios;
    }
}
