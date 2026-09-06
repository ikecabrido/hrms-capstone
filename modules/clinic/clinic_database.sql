CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_medical_record` (IN `p_patient_id` INT, IN `p_chief_complaint` TEXT, IN `p_diagnosis` TEXT, IN `p_treatment` TEXT, IN `p_consultation_type` VARCHAR(20), IN `p_attending_physician` VARCHAR(150), IN `p_created_by` INT)   BEGIN
    
    INSERT INTO cm_medical_records (
        patient_id,
        visit_date,
        chief_complaint,
        diagnosis,
        treatment,
        consultation_type,
        attending_physician,
        created_by
    ) VALUES (
        p_patient_id,
        NOW(),
        p_chief_complaint,
        p_diagnosis,
        p_treatment,
        p_consultation_type,
        p_attending_physician,
        p_created_by
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_clinic_reports`
--

CREATE TABLE `cm_clinic_reports` (
  `report_id` int(10) NOT NULL,
  `report_type` enum('Daily','Weekly','Monthly','Custom','Annual') DEFAULT NULL,
  `report_date` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(10) DEFAULT NULL,
  `status` enum('Generated','Processing','Error') DEFAULT 'Generated',
  `file_path` varchar(500) DEFAULT NULL,
  `file_format` enum('PDF','Excel','HTML','JSON') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_document_attachments`
--

CREATE TABLE `cm_document_attachments` (
  `attachment_id` int(10) NOT NULL,
  `record_id` int(10) DEFAULT NULL,
  `document_type` enum('Lab Result','X-Ray','Prescription','Medical Certificate','Other') DEFAULT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_emergency_cases`
--

CREATE TABLE `cm_emergency_cases` (
  `case_id` int(10) NOT NULL,
  `patient_id` int(10) NOT NULL,
  `incident_date` datetime NOT NULL,
  `incident_type` enum('Accident','Medical Emergency','Injury','Other','Illness','Fainting','Allergic Reaction') DEFAULT 'Other',
  `severity_level` enum('Low','Medium','High','Critical','Minor') DEFAULT 'Medium',
  `chief_complaint` text DEFAULT NULL,
  `initial_assessment` text DEFAULT NULL,
  `treatment_provided` text DEFAULT NULL,
  `attending_staff` varchar(255) DEFAULT NULL,
  `case_status` enum('Active','Resolved','Transferred','Closed','Open') DEFAULT 'Active',
  `ambulance_called` tinyint(1) DEFAULT 0,
  `ambulance_arrival_time` datetime DEFAULT NULL,
  `parents_notified` tinyint(1) DEFAULT 0,
  `parent_notification_time` datetime DEFAULT NULL,
  `witness_names` text DEFAULT NULL,
  `transfer_hospital` varchar(200) DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `cm_emergency_cases`
--
DELIMITER $$
CREATE TRIGGER `tr_emergency_case_close` BEFORE UPDATE ON `cm_emergency_cases` FOR EACH ROW BEGIN
    IF NEW.case_status = 'Closed' AND OLD.case_status != 'Closed' THEN
        SET NEW.updated_at = NOW();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_medical_records`
--

CREATE TABLE `cm_medical_records` (
  `record_id` int(10) NOT NULL,
  `patient_id` int(10) NOT NULL,
  `visit_date` datetime NOT NULL,
  `examination` varchar(100) DEFAULT NULL,
  `chief_complaint` text NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `consultation_type` enum('Walk-in','Appointment','Emergency','Follow-up') DEFAULT NULL,
  `status` enum('Completed','Pending','Follow-up','Follow-up Required') DEFAULT 'Pending',
  `attending_physician` varchar(150) DEFAULT NULL,
  `vital_signs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vital_signs`)),
  `medications_prescribed` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cm_medical_records`
  ADD COLUMN IF NOT EXISTS `examination` varchar(100) DEFAULT NULL AFTER `visit_date`,
  MODIFY `status` enum('Completed','Pending','Follow-up','Follow-up Required') DEFAULT 'Pending';

-- --------------------------------------------------------

--
-- Table structure for table `cm_medicine_inventory`
--

CREATE TABLE `cm_medicine_inventory` (
  `medicine_id` int(10) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `dosage_form` enum('Tablet','Capsule','Liquid','Injection','Ointment','Other') DEFAULT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(8,2) DEFAULT NULL,
  `selling_price` decimal(8,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_id` int(10) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `storage_requirements` text DEFAULT NULL,
  `status` enum('Available','Low Stock','Out of Stock','Expired') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cm_medicine_inventory`
--

INSERT INTO `cm_medicine_inventory` (`medicine_id`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `current_stock`, `reorder_level`, `unit_cost`, `selling_price`, `expiry_date`, `supplier_id`, `manufacturer`, `storage_requirements`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'Paracetamol', 'Acetaminophen', 'Analgesic', 'Tablet', '500mg', 500, 50, 2.50, NULL, '2026-12-31', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(2, 'Ibuprofen', 'Ibuprofen', 'Analgesic', 'Tablet', '400mg', 300, 30, 3.75, NULL, '2026-11-30', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(3, 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', 200, 25, 8.50, NULL, '2026-10-31', 2, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(4, 'Omeprazole', 'Omeprazole', 'Antacid', 'Capsule', '20mg', 150, 20, 6.25, NULL, '2027-01-31', 2, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(5, 'Loratadine', 'Loratadine', 'Antihistamine', 'Tablet', '10mg', 400, 40, 4.00, NULL, '2026-09-30', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL);

--
-- Triggers `cm_medicine_inventory`
--
DELIMITER $$
CREATE TRIGGER `tr_medicine_stock_update` BEFORE UPDATE ON `cm_medicine_inventory` FOR EACH ROW BEGIN
    IF NEW.expiry_date < CURDATE() THEN
        SET NEW.status = 'Expired';
    ELSEIF NEW.current_stock <= 0 THEN
        SET NEW.status = 'Out of Stock';
    ELSEIF NEW.current_stock <= NEW.reorder_level THEN
        SET NEW.status = 'Low Stock';
    ELSE
        SET NEW.status = 'Available';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_medicine_usage_logs`
--

CREATE TABLE `cm_medicine_usage_logs` (
  `log_id` int(10) NOT NULL,
  `medicine_id` int(10) NOT NULL,
  `record_id` int(10) DEFAULT NULL,
  `usage_date` datetime NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `remaining_stock` int(11) NOT NULL,
  `purpose` varchar(200) DEFAULT NULL,
  `used_by` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_patients`
--

CREATE TABLE `cm_patients` (
  `patient_id` int(10) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `patient_type` enum('Staff','Faculty') DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_suppliers`
--

CREATE TABLE `cm_suppliers` (
  `supplier_id` int(10) NOT NULL,
  `supplier_code` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cm_suppliers`
--

INSERT INTO `cm_suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `status`, `created_at`) VALUES
(1, 'MEDSUP001', 'MediCare Pharmaceuticals', 'John Smith', '123-456-7890', 'john@medicare.com', NULL, NULL, 'Active', '2026-08-15 14:36:16'),
(2, 'MEDSUP002', 'HealthPlus Supplies', 'Maria Santos', '098-765-4321', 'maria@healthplus.com', NULL, NULL, 'Active', '2026-08-15 14:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `cm_vital_signs`
--

CREATE TABLE `cm_vital_signs` (
  `vital_sign_id` int(10) NOT NULL,
  `record_id` int(10) NOT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `oxygen_saturation` decimal(3,1) DEFAULT NULL,
  `blood_sugar` decimal(5,1) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
