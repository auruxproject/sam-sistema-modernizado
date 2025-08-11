<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;

/**
 * Exportar model for handling data export in multiple formats
 *
 * @property int $formato Export format (1=LibreOffice, 2=Excel, 3=Text)
 * @property string $titulo Title for the exported file
 * @property string $detalle Description for LibreOffice/Excel formats
 * @property int $delimitador Field delimiter for text format
 * @property string $delimitadorotro Custom delimiter when delimitador=5
 * @property string $separadorfila Row separator for text format
 * @property int $incluirtitulo Whether to include title in text format
 * @property array $campos_desc Field descriptions/headers
 * @property array $datos Data to export
 * @property string $action Action name for data retrieval
 * @property string $parametros JSON parameters for data retrieval
 */
class Exportar extends Model
{
    // Export format constants
    const FORMAT_LIBREOFFICE = 1;
    const FORMAT_EXCEL = 2;
    const FORMAT_TEXT = 3;
    
    // Delimiter constants
    const DELIMITER_TAB = 1;
    const DELIMITER_PIPE = 2;
    const DELIMITER_COMMA = 3;
    const DELIMITER_SEMICOLON = 4;
    const DELIMITER_CUSTOM = 5;
    
    // Row separator constants
    const ROW_SEPARATOR_LF = 'LF';
    const ROW_SEPARATOR_CR = 'CR';
    
    // Maximum limits for security
    const MAX_TITLE_LENGTH = 255;
    const MAX_DETAIL_LENGTH = 1000;
    const MAX_CUSTOM_DELIMITER_LENGTH = 10;
    const MAX_ROWS_EXPORT = 50000;
    const MAX_COLUMNS_EXPORT = 100;
    
    public $formato;
    public $titulo;
    public $detalle;
    public $delimitador;
    public $delimitadorotro;
    public $separadorfila;
    public $incluirtitulo;
    public $campos_desc;
    public $datos;
    public $action;
    public $parametros;
    
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();
        
        // Set default values
        $this->formato = self::FORMAT_LIBREOFFICE;
        $this->delimitador = self::DELIMITER_TAB;
        $this->separadorfila = self::ROW_SEPARATOR_LF;
        $this->incluirtitulo = 1;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['formato'], 'required'],
            [['titulo'], 'required', 'when' => function($model) {
                return $model->formato == self::FORMAT_TEXT && $model->incluirtitulo;
            }],
            [['campos_desc', 'datos'], 'required'],
            
            // Integer validations
            [['formato', 'delimitador', 'incluirtitulo'], 'integer'],
            
            // Format validation
            ['formato', 'in', 'range' => [self::FORMAT_LIBREOFFICE, self::FORMAT_EXCEL, self::FORMAT_TEXT]],
            
            // Delimiter validation
            ['delimitador', 'in', 'range' => [self::DELIMITER_TAB, self::DELIMITER_PIPE, self::DELIMITER_COMMA, self::DELIMITER_SEMICOLON, self::DELIMITER_CUSTOM]],
            
            // Custom delimiter validation
            ['delimitadorotro', 'required', 'when' => function($model) {
                return $model->delimitador == self::DELIMITER_CUSTOM;
            }],
            ['delimitadorotro', 'string', 'max' => self::MAX_CUSTOM_DELIMITER_LENGTH],
            
            // String validations
            ['titulo', 'string', 'max' => self::MAX_TITLE_LENGTH],
            ['detalle', 'string', 'max' => self::MAX_DETAIL_LENGTH],
            ['separadorfila', 'in', 'range' => [self::ROW_SEPARATOR_LF, self::ROW_SEPARATOR_CR]],
            
            // JSON validations
            [['action', 'parametros'], 'string'],
            
            // Data validations
            [['campos_desc', 'datos'], 'validateDataStructure'],
            
            // Trim whitespace
            [['titulo', 'detalle', 'delimitadorotro', 'action', 'parametros'], 'trim'],
            
            // Security validations
            [['titulo', 'detalle'], 'filter', 'filter' => function($value) {
                return Html::encode($value);
            }],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'formato' => 'Formato de Exportación',
            'titulo' => 'Título',
            'detalle' => 'Detalle',
            'delimitador' => 'Delimitador de Campo',
            'delimitadorotro' => 'Delimitador Personalizado',
            'separadorfila' => 'Separador de Fila',
            'incluirtitulo' => 'Incluir Título',
            'campos_desc' => 'Descripción de Campos',
            'datos' => 'Datos',
            'action' => 'Acción',
            'parametros' => 'Parámetros',
        ];
    }
    
    /**
     * Validates data structure for export
     */
    public function validateDataStructure($attribute, $params): void
    {
        if ($attribute === 'campos_desc') {
            if (!is_array($this->campos_desc)) {
                $this->addError($attribute, 'La descripción de campos debe ser un array.');
                return;
            }
            
            if (count($this->campos_desc) > self::MAX_COLUMNS_EXPORT) {
                $this->addError($attribute, 'Demasiadas columnas para exportar (máximo: ' . self::MAX_COLUMNS_EXPORT . ').');
            }
            
            // Validate field descriptions
            foreach ($this->campos_desc as $index => $desc) {
                if (!is_string($desc) || empty(trim($desc))) {
                    $this->addError($attribute, "La descripción del campo en la posición {$index} no es válida.");
                }
            }
        }
        
        if ($attribute === 'datos') {
            if (!is_array($this->datos)) {
                $this->addError($attribute, 'Los datos deben ser un array.');
                return;
            }
            
            if (count($this->datos) > self::MAX_ROWS_EXPORT) {
                $this->addError($attribute, 'Demasiadas filas para exportar (máximo: ' . self::MAX_ROWS_EXPORT . ').');
                return;
            }
            
            // Validate data structure
            $expectedColumns = count($this->campos_desc ?? []);
            foreach ($this->datos as $rowIndex => $row) {
                if (!is_array($row)) {
                    $this->addError($attribute, "La fila {$rowIndex} no es un array válido.");
                    continue;
                }
                
                if ($expectedColumns > 0 && count($row) !== $expectedColumns) {
                    $this->addError($attribute, "La fila {$rowIndex} no tiene el número correcto de columnas (esperadas: {$expectedColumns}, encontradas: " . count($row) . ").");
                }
            }
        }
    }
    
    /**
     * Exports data in the specified format
     *
     * @return string|array|null The exported data
     * @throws ServerErrorHttpException
     */
    public function exportar()
    {
        if (!$this->validate()) {
            throw new ServerErrorHttpException('Datos de exportación no válidos: ' . implode(', ', $this->getFirstErrors()));
        }
        
        try {
            switch ($this->formato) {
                case self::FORMAT_LIBREOFFICE:
                    return $this->exportToLibreOffice();
                    
                case self::FORMAT_EXCEL:
                    return $this->exportToExcel();
                    
                case self::FORMAT_TEXT:
                    return $this->exportToText();
                    
                default:
                    throw new ServerErrorHttpException('Formato de exportación no válido.');
            }
        } catch (\Exception $e) {
            Yii::error('Export error: ' . $e->getMessage(), __METHOD__);
            throw new ServerErrorHttpException('Error durante la exportación: ' . $e->getMessage());
        }
    }
    
    /**
     * Exports data to LibreOffice format (HTML table)
     *
     * @return string
     */
    private function exportToLibreOffice(): string
    {
        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        
        // Add title if provided
        if (!empty($this->titulo)) {
            $html .= '<caption><h2>' . Html::encode($this->titulo) . '</h2></caption>';
        }
        
        // Add detail if provided
        if (!empty($this->detalle)) {
            $html .= '<caption><p>' . Html::encode($this->detalle) . '</p></caption>';
        }
        
        // Add header row
        $html .= '<thead><tr>';
        foreach ($this->campos_desc as $campo) {
            $html .= '<th>' . Html::encode($campo) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Add data rows
        $html .= '<tbody>';
        foreach ($this->datos as $fila) {
            $html .= '<tr>';
            foreach ($fila as $valor) {
                $html .= '<td>' . Html::encode($valor) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    /**
     * Exports data to Excel format (associative array)
     *
     * @return array
     */
    private function exportToExcel(): array
    {
        $exportData = [];
        
        foreach ($this->datos as $fila) {
            $row = [];
            foreach ($fila as $index => $valor) {
                if (isset($this->campos_desc[$index])) {
                    $row[$this->campos_desc[$index]] = $valor;
                }
            }
            $exportData[] = $row;
        }
        
        return $exportData;
    }
    
    /**
     * Exports data to text format
     *
     * @return string
     */
    private function exportToText(): string
    {
        // Get field delimiter
        $delimiter = $this->getFieldDelimiter();
        
        // Get row separator
        $rowSeparator = $this->getRowSeparator();
        
        $text = '';
        
        // Add title if requested
        if ($this->incluirtitulo && !empty($this->titulo)) {
            $text .= Html::encode($this->titulo) . $rowSeparator;
        }
        
        // Add header row
        $headerRow = [];
        foreach ($this->campos_desc as $campo) {
            $headerRow[] = $this->escapeTextValue($campo, $delimiter);
        }
        $text .= implode($delimiter, $headerRow) . $rowSeparator;
        
        // Add data rows
        foreach ($this->datos as $fila) {
            $dataRow = [];
            foreach ($fila as $valor) {
                $dataRow[] = $this->escapeTextValue($valor, $delimiter);
            }
            $text .= implode($delimiter, $dataRow) . $rowSeparator;
        }
        
        return $text;
    }
    
    /**
     * Gets the field delimiter character
     *
     * @return string
     */
    private function getFieldDelimiter(): string
    {
        switch ($this->delimitador) {
            case self::DELIMITER_TAB:
                return "\t";
            case self::DELIMITER_PIPE:
                return '|';
            case self::DELIMITER_COMMA:
                return ',';
            case self::DELIMITER_SEMICOLON:
                return ';';
            case self::DELIMITER_CUSTOM:
                return $this->delimitadorotro ?? ',';
            default:
                return "\t";
        }
    }
    
    /**
     * Gets the row separator
     *
     * @return string
     */
    private function getRowSeparator(): string
    {
        switch ($this->separadorfila) {
            case self::ROW_SEPARATOR_LF:
                return "\n";
            case self::ROW_SEPARATOR_CR:
                return "\r\n";
            default:
                return "\n";
        }
    }
    
    /**
     * Escapes text values for CSV/text export
     *
     * @param mixed $value
     * @param string $delimiter
     * @return string
     */
    private function escapeTextValue($value, string $delimiter): string
    {
        $value = (string) $value;
        
        // If value contains delimiter, newlines, or quotes, wrap in quotes
        if (strpos($value, $delimiter) !== false || 
            strpos($value, "\n") !== false || 
            strpos($value, "\r") !== false || 
            strpos($value, '"') !== false) {
            
            // Escape quotes by doubling them
            $value = str_replace('"', '""', $value);
            $value = '"' . $value . '"';
        }
        
        return $value;
    }
    
    /**
     * Gets available export formats
     *
     * @return array
     */
    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_LIBREOFFICE => 'LibreOffice (HTML)',
            self::FORMAT_EXCEL => 'Excel (Array)',
            self::FORMAT_TEXT => 'Texto (CSV)',
        ];
    }
    
    /**
     * Gets available delimiter options
     *
     * @return array
     */
    public static function getDelimiterOptions(): array
    {
        return [
            self::DELIMITER_TAB => 'Tabulación',
            self::DELIMITER_PIPE => 'Línea Vertical (|)',
            self::DELIMITER_COMMA => 'Coma (,)',
            self::DELIMITER_SEMICOLON => 'Punto y Coma (;)',
            self::DELIMITER_CUSTOM => 'Personalizado',
        ];
    }
    
    /**
     * Gets available row separator options
     *
     * @return array
     */
    public static function getRowSeparatorOptions(): array
    {
        return [
            self::ROW_SEPARATOR_LF => 'Salto de Línea (LF)',
            self::ROW_SEPARATOR_CR => 'Retorno de Carro (CR)',
        ];
    }
    
    /**
     * Legacy method for backward compatibility
     *
     * @return string|array|null
     * @deprecated Use exportar() instead
     */
    public function Exportar()
    {
        return $this->exportar();
    }
}
