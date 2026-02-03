<?php

/**
 * MedicationService
 * Handles medication-related business logic and database operations
 */

require_once __DIR__ . '/../config/database.php';

class MedicationService {
    
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Get all medications for a user
     * 
     * @param int $userId The user ID
     * @return array Array of medications
     */
    public function getUserMedications($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_medications 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new medication for a user
     * 
     * @param int $userId The user ID
     * @param array $data Medication data
     * @return int|bool The medication ID or false on failure
     */
    public function addMedication($userId, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_medications 
            (user_id, medication_name, medication_id, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $userId,
            $data['medication_name'] ?? null,
            $data['medication_id'] ?? null
        ]);
        
        return $result ? $this->pdo->lastInsertId() : false;
    }
    
    /**
     * Get medication details by ID
     * 
     * @param int $medicationId The medication ID
     * @param int $userId The user ID (for authorization)
     * @return array|null Medication details or null
     */
    public function getMedicationById($medicationId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_medications 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$medicationId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Add a dose record for a medication
     * 
     * @param int $medicationId The medication ID
     * @param array $doseData Dose information
     * @return bool Success status
     */
    public function addDose($medicationId, $doseData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO medication_doses 
            (user_medication_id, dosage, dosage_unit, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $medicationId,
            $doseData['dosage'] ?? null,
            $doseData['dosage_unit'] ?? null
        ]);
    }
    
    /**
     * Add a schedule for a medication
     * 
     * @param int $medicationId The medication ID
     * @param array $scheduleData Schedule information
     * @return bool Success status
     */
    public function addSchedule($medicationId, $scheduleData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO medication_schedules 
            (user_medication_id, frequency, time_of_day, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $medicationId,
            $scheduleData['frequency'] ?? null,
            $scheduleData['time_of_day'] ?? null
        ]);
    }
    
    /**
     * Add instructions for a medication
     * 
     * @param int $medicationId The medication ID
     * @param string $instructions The instructions text
     * @return bool Success status
     */
    public function addInstructions($medicationId, $instructions) {
        $stmt = $this->pdo->prepare("
            UPDATE user_medications 
            SET instructions = ? 
            WHERE id = ?
        ");
        
        return $stmt->execute([$instructions, $medicationId]);
    }
    
    /**
     * Add a condition associated with a medication
     * 
     * @param int $medicationId The medication ID
     * @param array $conditionData Condition information
     * @return bool Success status
     */
    public function addCondition($medicationId, $conditionData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO medication_conditions 
            (user_medication_id, condition_name, condition_id, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $medicationId,
            $conditionData['condition_name'] ?? null,
            $conditionData['condition_id'] ?? null
        ]);
    }
    
    /**
     * Search medications using external API
     * 
     * @param string $query Search query
     * @return array Search results
     */
    public function searchMedications($query) {
        require_once __DIR__ . '/NhsApiClient.php';
        $apiClient = new NhsApiClient();
        return $apiClient->searchMedication($query);
    }
    
    /**
     * Get medication details from external API
     * 
     * @param string $medicationId External medication ID
     * @return array Medication details
     */
    public function getMedicationDetailsFromApi($medicationId) {
        require_once __DIR__ . '/NhsApiClient.php';
        $apiClient = new NhsApiClient();
        return $apiClient->getMedicationDetails($medicationId);
    }
}
