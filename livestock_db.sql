-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 01:02 PM
-- Server version: 8.0.46
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `livestock_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `HouseNumber` varchar(45) DEFAULT NULL,
  `Street` varchar(50) NOT NULL,
  `Barangay` varchar(45) NOT NULL,
  `Municipality_City` varchar(45) NOT NULL,
  `Province` varchar(50) NOT NULL,
  `ZipCode` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `user_id`, `HouseNumber`, `Street`, `Barangay`, `Municipality_City`, `Province`, `ZipCode`) VALUES
('ADD-00001', 'USE-00001', 'Lot 14 Blk 3', 'Mayon Highland Road', 'Buyuan', 'Legazpi City', 'Albay', '4500'),
('ADD-00002', 'USE-00002', 'Purok 4', 'Maharlika Highway', 'Anayan', 'Pili', 'Camarines Sur', '4418'),
('ADD-00003', 'USE-00003', '118', 'Rizal Street', 'Sagpon', 'Daraga', 'Albay', '4501'),
('ADD-00004', 'USE-00004', NULL, 'Purok 2', 'Binitayan', 'Daraga', 'Albay', '4501'),
('ADD-00005', 'USE-00005', '42-B', 'Peñafrancia Avenue', 'San Felipe', 'Naga City', 'Camarines Sur', '4400'),
('ADD-00006', 'USE-00006', '19', 'San Jose Street', 'San Isidro', 'Pili', 'Camarines Sur', '4418'),
('ADD-00007', 'USE-00007', NULL, 'Zone 5', 'Concepcion Pequeña', 'Naga City', 'Camarines Sur', '4400'),
('ADD-00008', 'USE-00008', '87', 'Magsaysay Avenue', 'Balatas', 'Naga City', 'Camarines Sur', '4400'),
('ADD-00009', 'USE-00009', '205', 'Diversion Road', 'Palestina', 'Pili', 'Camarines Sur', '4418');

-- --------------------------------------------------------

--
-- Table structure for table `capital`
--

CREATE TABLE `capital` (
  `cap_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `cap_description` varchar(350) DEFAULT NULL,
  `cap_amount` decimal(10,2) NOT NULL,
  `date_invested` date NOT NULL
) ;

--
-- Dumping data for table `capital`
--

INSERT INTO `capital` (`cap_id`, `user_id`, `cap_description`, `cap_amount`, `date_invested`) VALUES
('CAP-00001', 'USE-00001', 'Initial Brahman cattle herd acquisition and pasture fencing', 350000.00, '2018-05-12'),
('CAP-00002', 'USE-00001', 'Concrete piggery pens and automated nipple drinker system', 220000.00, '2019-02-18'),
('CAP-00003', 'USE-00001', 'Boer goat breeding stock and elevated slatted housing', 145000.00, '2020-06-25'),
('CAP-00004', 'USE-00001', 'Feed mill mixer, storage warehouse, and initial feed stock', 180000.00, '2021-04-10'),
('CAP-00005', 'USE-00001', 'Deep-well water pump and solar power backup system', 125000.00, '2022-01-19'),
('CAP-00006', 'USE-00001', 'Simmental and Native cattle fattening expansion', 290000.00, '2022-11-08'),
('CAP-00007', 'USE-00001', 'Livestock transport utility truck and digital platform scale', 410000.00, '2023-08-14'),
('CAP-00008', 'USE-00001', 'Biogas digester and waste lagoon sanitation upgrade', 165000.00, '2024-05-22'),
('CAP-00009', 'USE-00001', NULL, 95000.00, '2025-03-11'),
('CAP-00010', 'USE-00001', 'Quarantine barn and perimeter biosecurity disinfectant bay', 130000.00, '2026-01-15'),
('CAP-00011', 'USE-00002', 'Initial Large White and Landrace swine breeder stock', 280000.00, '2019-09-20'),
('CAP-00012', 'USE-00002', 'Broiler and layer poultry houses with brooding heaters', 240000.00, '2020-04-14'),
('CAP-00013', 'USE-00002', 'Philippine Carabao and Murrah dairy stock acquisition', 320000.00, '2021-07-03'),
('CAP-00014', 'USE-00002', 'Portable milking machine and stainless bulk cooling tank', 175000.00, '2022-03-29'),
('CAP-00015', 'USE-00002', 'Anglo-Nubian and Saanen dairy goat barn construction', 155000.00, '2022-12-17'),
('CAP-00016', 'USE-00002', 'Automated egg grading table and poultry cages', 110000.00, '2023-09-05'),
('CAP-00017', 'USE-00002', 'Forage chopper and Napier grass irrigation system', 140000.00, '2024-06-18'),
('CAP-00018', 'USE-00002', NULL, 85000.00, '2025-02-24'),
('CAP-00019', 'USE-00002', 'Duroc and Pietrain boar semen lab and AI equipment', 195000.00, '2025-10-30'),
('CAP-00020', 'USE-00002', 'Cold storage unit and veterinary supply dispensary', 210000.00, '2026-02-10');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `expense_description` text,
  `date_recorded` datetime NOT NULL
) ;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `user_id`, `amount`, `category`, `expense_description`, `date_recorded`) VALUES
('EXP-00001', 'USE-00001', 28500.00, 'Feeds & Nutrition', '60 bags of grower and finisher cattle/swine pellets', '2026-01-18 09:15:00'),
('EXP-00002', 'USE-00003', 12400.00, 'Veterinary & Medicine', 'FMD vaccines, dewormers, and multivitamin injectables', '2026-02-14 10:30:00'),
('EXP-00003', 'USE-00004', 8750.50, 'Utilities', 'Monthly farm electricity and deep-well pump billing', '2026-03-10 14:20:00'),
('EXP-00004', 'USE-00005', 15200.00, 'Equipment & Maintenance', 'Repair of pasture barbed wire fence and pen gates', '2026-04-08 11:05:00'),
('EXP-00005', 'USE-00001', 34000.00, 'Labor & Wages', 'Monthly payroll for Business 1 farm caretakers', '2026-05-01 16:00:00'),
('EXP-00006', 'USE-00003', 19800.00, 'Feeds & Nutrition', 'Molasses, silage, and mineral lick blocks', '2026-06-12 08:45:00'),
('EXP-00007', 'USE-00004', 9600.00, 'Biosecurity & Sanitation', 'Disinfectant drums, agricultural lime, and footbath mats', '2026-07-19 13:10:00'),
('EXP-00008', 'USE-00005', 11350.00, 'Transport & Logistics', 'Diesel fuel and toll fees for abattoir cattle delivery', '2026-08-22 15:40:00'),
('EXP-00009', 'USE-00003', 6450.00, 'Veterinary & Medicine', NULL, '2026-09-16 10:12:00'),
('EXP-00010', 'USE-00001', 31200.00, 'Feeds & Nutrition', 'Bulk order of cracked corn, rice bran, and copra meal', '2026-10-25 09:50:00'),
('EXP-00011', 'USE-00002', 26800.00, 'Feeds & Nutrition', '80 sacks of broiler starter crumble and layer mash', '2026-01-20 08:30:00'),
('EXP-00012', 'USE-00006', 14900.00, 'Veterinary & Medicine', 'Hog cholera vaccines, iron dextran, and antibiotics', '2026-02-25 11:15:00'),
('EXP-00013', 'USE-00007', 10500.00, 'Utilities', 'Electricity for poultry brooders and milk cooling tank', '2026-03-19 14:55:00'),
('EXP-00014', 'USE-00008', 18400.00, 'Livestock Purchase', '12 weaned Large White piglets for grow-out batch', '2026-04-16 09:40:00'),
('EXP-00015', 'USE-00002', 42000.00, 'Labor & Wages', 'Monthly wages for 4 Business 2 farm staff', '2026-05-02 16:30:00'),
('EXP-00016', 'USE-00009', 13750.00, 'Equipment & Maintenance', 'Replacement infrared brooder lamps and drinker valves', '2026-06-21 10:25:00'),
('EXP-00017', 'USE-00006', 8900.00, 'Biosecurity & Sanitation', 'Waste lagoon enzymes and poultry house sanitizers', '2026-07-28 15:10:00'),
('EXP-00018', 'USE-00007', 22600.00, 'Feeds & Nutrition', 'Grower hog pellets and dairy carabao concentrates', '2026-08-30 08:20:00'),
('EXP-00019', 'USE-00008', 7300.00, 'Transport & Logistics', NULL, '2026-09-24 12:45:00'),
('EXP-00020', 'USE-00009', 16500.00, 'Veterinary & Medicine', 'Artificial insemination service and pregnancy ultrasound', '2026-10-29 11:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `livestock`
--

CREATE TABLE `livestock` (
  `livestock_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `animal_type` varchar(50) NOT NULL,
  `breed` varchar(50) NOT NULL,
  `quantity` int NOT NULL,
  `weight` decimal(8,2) NOT NULL,
  `health_status` varchar(50) NOT NULL,
  `vaccination_status` varchar(50) DEFAULT NULL,
  `vaccination_date` date DEFAULT NULL,
  `date_recorded` datetime NOT NULL
) ;

--
-- Dumping data for table `livestock`
--

INSERT INTO `livestock` (`livestock_id`, `user_id`, `animal_type`, `breed`, `quantity`, `weight`, `health_status`, `vaccination_status`, `vaccination_date`, `date_recorded`) VALUES
('LIV-00001', 'USE-00001', 'Cattle', 'Batangas Cattle', 25, 415.50, 'Healthy', 'Vaccinated', '2026-03-15', '2026-01-10 08:00:00'),
('LIV-00002', 'USE-00003', 'Swine', 'Large White x Native', 70, 86.20, 'Healthy', 'Vaccinated', '2026-04-05', '2026-01-15 09:15:00'),
('LIV-00003', 'USE-00004', 'Goat', 'Boer x Native Goat', 45, 42.80, 'Healthy', 'Vaccinated', '2026-04-20', '2026-02-02 10:30:00'),
('LIV-00004', 'USE-00005', 'Carabao', 'Philippine Carabao', 18, 475.00, 'Healthy', 'Vaccinated', '2026-03-28', '2026-02-14 11:45:00'),
('LIV-00005', 'USE-00003', 'Swine', 'Landrace x Native', 55, 84.50, 'Quarantined', 'Partial', '2026-06-10', '2026-03-04 08:50:00'),
('LIV-00006', 'USE-00004', 'Goat', 'Philippine Native Goat', 30, 31.40, 'Healthy', 'Unvaccinated', NULL, '2026-03-19 13:10:00'),
('LIV-00007', 'USE-00005', 'Cattle', 'Iloilo Native Cattle', 22, 392.00, 'Recovered', 'Vaccinated', '2026-05-12', '2026-04-01 14:20:00'),
('LIV-00008', 'USE-00001', 'Swine', 'Berkjala', 40, 78.00, 'Healthy', 'Vaccinated', '2026-05-25', '2026-04-18 09:35:00'),
('LIV-00009', 'USE-00003', 'Goat', 'Dadiangas Native Goat', 28, 34.25, 'Sick', 'Partial', '2026-06-18', '2026-05-07 15:00:00'),
('LIV-00010', 'USE-00004', 'Swine', 'Kalinga Native Pig', 12, 64.60, 'Deceased', 'Partial', '2026-06-02', '2026-05-20 10:15:00'),
('LIV-00011', 'USE-00002', 'Chicken', 'Darag Native Chicken', 500, 1.55, 'Healthy', 'Vaccinated', '2026-03-12', '2026-01-12 07:45:00'),
('LIV-00012', 'USE-00006', 'Duck', 'Pateros Duck (Itik)', 450, 1.65, 'Healthy', 'Vaccinated', '2026-03-25', '2026-01-24 08:30:00'),
('LIV-00013', 'USE-00007', 'Carabao', 'Philippine Carabao', 16, 465.00, 'Healthy', 'Vaccinated', '2026-02-19', '2026-02-05 09:50:00'),
('LIV-00014', 'USE-00008', 'Swine', 'Philippine Native Pig', 60, 68.40, 'Healthy', 'Vaccinated', '2026-04-14', '2026-02-22 11:10:00'),
('LIV-00015', 'USE-00009', 'Goat', 'Anglo-Nubian x Native', 35, 43.60, 'Healthy', 'Vaccinated', '2026-04-29', '2026-03-09 13:25:00'),
('LIV-00016', 'USE-00006', 'Carabao', 'Murrah x Philippine Carabao', 14, 505.50, 'Healthy', 'Vaccinated', '2026-03-08', '2026-03-27 14:40:00'),
('LIV-00017', 'USE-00007', 'Chicken', 'Banaba Native Chicken', 320, 1.70, 'Healthy', 'Unvaccinated', NULL, '2026-04-11 08:15:00'),
('LIV-00018', 'USE-00008', 'Goat', 'Philippine Native Goat', 26, 32.90, 'Recovered', 'Vaccinated', '2026-05-16', '2026-04-26 10:05:00'),
('LIV-00019', 'USE-00009', 'Duck', 'Muscovy Duck (Pato)', 220, 2.85, 'Healthy', 'Vaccinated', '2026-06-05', '2026-05-14 15:30:00'),
('LIV-00020', 'USE-00002', 'Swine', 'Quezon Native Pig', 48, 66.75, 'Quarantined', 'Partial', '2026-06-21', '2026-06-01 11:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `user_id` varchar(45) NOT NULL,
  `address_id` varchar(45) NOT NULL,
  `bus_started` date NOT NULL,
  `TIN_num` varchar(15) NOT NULL,
  `bus_ContactNum` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`user_id`, `address_id`, `bus_started`, `TIN_num`, `bus_ContactNum`) VALUES
('USE-00001', 'ADD-00001', '2018-05-10', '114-289-301', '0917-800-20111'),
('USE-00002', 'ADD-00002', '2019-09-15', '228-415-609', '0918-900-20222');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `report_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_expenses` decimal(10,2) NOT NULL,
  `net_profit_loss` decimal(10,2) NOT NULL,
  `roi` decimal(5,2) NOT NULL,
  `date_generated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`report_id`, `user_id`, `total_sales`, `total_expenses`, `net_profit_loss`, `roi`, `date_generated`) VALUES
('REP-00001', 'USE-00001', 1221500.00, 177250.50, 1044249.50, 49.49, '2026-10-31 09:00:00'),
('REP-00002', 'USE-00002', 1148500.00, 181600.00, 966900.00, 45.82, '2026-10-31 09:15:00'),
('REP-00003', 'USE-00001', 305000.00, 49650.00, 255350.00, 12.10, '2026-06-30 08:30:00'),
('REP-00004', 'USE-00002', 69000.00, 70600.00, -1600.00, -0.08, '2026-06-30 08:45:00'),
('REP-00005', 'USE-00001', 210000.00, 43600.00, 166400.00, 7.89, '2026-07-31 09:00:00'),
('REP-00006', 'USE-00002', 368000.00, 31500.00, 336500.00, 15.95, '2026-07-31 09:10:00'),
('REP-00007', 'USE-00001', 309000.00, 11350.00, 297650.00, 14.11, '2026-08-31 09:05:00'),
('REP-00008', 'USE-00002', 211500.00, 22600.00, 188900.00, 8.95, '2026-08-31 09:20:00'),
('REP-00009', 'USE-00001', 417500.00, 6450.00, 411050.00, 19.48, '2026-09-30 08:50:00'),
('REP-00010', 'USE-00002', 67000.00, 7300.00, 59700.00, 2.83, '2026-09-30 09:00:00'),
('REP-00011', 'USE-00001', 60000.00, 31200.00, 28800.00, 1.36, '2026-10-31 09:30:00'),
('REP-00012', 'USE-00002', 244000.00, 16500.00, 227500.00, 10.78, '2026-10-31 09:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sales_id` varchar(45) NOT NULL,
  `livestock_id` varchar(45) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `quantity_sold` int NOT NULL,
  `date_sold` date NOT NULL
) ;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sales_id`, `livestock_id`, `amount`, `quantity_sold`, `date_sold`) VALUES
('SLS-00001', 'LIV-00001', 160000.00, 4, '2026-06-15'),
('SLS-00002', 'LIV-00002', 145000.00, 10, '2026-06-22'),
('SLS-00003', 'LIV-00003', 75000.00, 10, '2026-07-04'),
('SLS-00004', 'LIV-00004', 135000.00, 3, '2026-07-18'),
('SLS-00005', 'LIV-00006', 39000.00, 6, '2026-08-02'),
('SLS-00006', 'LIV-00007', 114000.00, 3, '2026-08-14'),
('SLS-00007', 'LIV-00008', 156000.00, 12, '2026-08-29'),
('SLS-00008', 'LIV-00001', 200000.00, 5, '2026-09-12'),
('SLS-00009', 'LIV-00002', 217500.00, 15, '2026-09-27'),
('SLS-00010', 'LIV-00003', 60000.00, 8, '2026-10-15'),
('SLS-00011', 'LIV-00011', 42000.00, 120, '2026-06-18'),
('SLS-00012', 'LIV-00012', 27000.00, 90, '2026-06-28'),
('SLS-00013', 'LIV-00013', 176000.00, 4, '2026-07-09'),
('SLS-00014', 'LIV-00014', 192000.00, 16, '2026-07-24'),
('SLS-00015', 'LIV-00015', 67500.00, 9, '2026-08-06'),
('SLS-00016', 'LIV-00016', 144000.00, 3, '2026-08-19'),
('SLS-00017', 'LIV-00017', 28000.00, 80, '2026-09-03'),
('SLS-00018', 'LIV-00018', 39000.00, 6, '2026-09-18'),
('SLS-00019', 'LIV-00019', 28000.00, 70, '2026-10-04'),
('SLS-00020', 'LIV-00014', 216000.00, 18, '2026-10-22');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `user_id` varchar(45) NOT NULL,
  `date_hired` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`user_id`, `date_hired`) VALUES
('USE-00003', '2021-03-01'),
('USE-00004', '2022-07-15'),
('USE-00005', '2023-11-02'),
('USE-00006', '2021-06-10'),
('USE-00007', '2022-01-20'),
('USE-00008', '2023-08-14'),
('USE-00009', '2024-04-01');

-- --------------------------------------------------------

--
-- Table structure for table `task_schedule`
--

CREATE TABLE `task_schedule` (
  `task_id` varchar(45) NOT NULL,
  `user_id` varchar(45) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `task_description` text,
  `schedule_date` date NOT NULL,
  `task_status` varchar(50) NOT NULL
) ;

--
-- Dumping data for table `task_schedule`
--

INSERT INTO `task_schedule` (`task_id`, `user_id`, `task_name`, `task_description`, `schedule_date`, `task_status`) VALUES
('TSK-00001', 'USE-00003', 'Morning Cattle & Swine Feeding', 'Distribute grower pellets and check water troughs in Pens 1-6', '2026-11-01', 'Completed'),
('TSK-00002', 'USE-00004', 'Boer x Native Goat Deworming', 'Administer oral dewormer and record weights for 45 Boer cross goats', '2026-11-02', 'Completed'),
('TSK-00003', 'USE-00005', 'North Pasture Fence Inspection', 'Check and tighten barbed wire posts along Batangas cattle grazing paddock', '2026-11-03', 'Completed'),
('TSK-00004', 'USE-00003', 'Landrace x Native Quarantine Check', 'Log morning body temperature and feed intake of isolated swine', '2026-11-04', 'Completed'),
('TSK-00005', 'USE-00004', 'Piggery Biogas & Lagoon Check', 'Inspect sludge outlet valve and apply biosecurity lime', '2026-11-05', 'Completed'),
('TSK-00006', 'USE-00005', 'Forage & Silage Chopping', 'Harvest Napier grass from East plot and load into feed mixer', '2026-11-06', 'Cancelled'),
('TSK-00007', 'USE-00001', 'Monthly Feed & Expense Audit', 'Verify warehouse sack count against October supplier receipts', '2026-11-08', 'Completed'),
('TSK-00008', 'USE-00003', 'Dadiangas Goat Treatment Follow-up', 'Administer prescribed antibiotic dose to sick native goat batch', '2026-11-10', 'In Progress'),
('TSK-00009', 'USE-00004', 'Native Goat Vaccination Prep', 'Prepare cold-chain vaccine vials and restraint chute', '2026-11-12', 'Pending'),
('TSK-00010', 'USE-00005', 'Mid-Month Herd Weighing', 'Weigh Iloilo Native cattle and Philippine Carabao on platform scale', '2026-11-13', 'In Progress'),
('TSK-00011', 'USE-00006', 'Darag Brooder House Check', 'Verify 32°C heater temperature and electrolyte water lines', '2026-11-01', 'Completed'),
('TSK-00012', 'USE-00007', 'Murrah Cross Carabao Milking', 'Sanitize milking clusters and transfer milk to bulk cooling tank', '2026-11-02', 'Completed'),
('TSK-00013', 'USE-00008', 'Pateros Duck Egg Collection', 'Collect morning eggs from 450 Itik ducks and sort by weight', '2026-11-03', 'Completed'),
('TSK-00014', 'USE-00009', 'Native Pig Pen Sanitation', 'Pressure wash concrete pens and refill disinfectant footbaths', '2026-11-04', 'Completed'),
('TSK-00015', 'USE-00006', 'Banaba Native Chicken Vaccination', 'Administer Newcastle disease vaccine to 320 native chickens', '2026-11-05', 'Completed'),
('TSK-00016', 'USE-00007', 'Quezon Native Pig Quarantine Check', NULL, '2026-11-07', 'In Progress'),
('TSK-00017', 'USE-00008', 'Anglo-Nubian Cross Hoof Trim', 'Inspect and trim hooves of dairy goat herd', '2026-11-09', 'Pending'),
('TSK-00018', 'USE-00009', 'Muscovy Duck Pond Water Refresh', 'Drain and refill Pato wading basin and clean feeders', '2026-11-11', 'Completed'),
('TSK-00019', 'USE-00002', 'Buyer Delivery Schedule Review', 'Confirm wholesale native chicken and swine pickup slots for mid-November', '2026-11-12', 'Completed'),
('TSK-00020', 'USE-00006', 'Weekly Mortality & Egg Log', 'Compile daily Itik egg yield and poultry feed conversion report', '2026-11-13', 'In Progress');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(45) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `sex` varchar(25) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `address_id` varchar(45) NOT NULL,
  `user_type` char(1) NOT NULL
) ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `business_name`, `first_name`, `last_name`, `email`, `username`, `password`, `birthdate`, `sex`, `contact_number`, `address_id`, `user_type`) VALUES
('USE-00001', 'Mayon Highland Livestock', 'Jeremiah', 'Ong', 'jeoong@gmail.com', 'Jeoong01', 'B13ju1d7z', '1978-04-12', 'Male', '0917-412-10431', 'ADD-00001', 'O'),
('USE-00002', 'Bicol Verde Agri Farm', 'Nathan', 'Garcia', 'nathangarcia@gmail.com', 'Nathan02', 'B13ju1d7z', '1981-08-23', 'Male', '0918-534-10862', 'ADD-00002', 'O'),
('USE-00003', 'Mayon Highland Livestock', 'Ethan', 'Benares', 'ethanbenares@gmail.com', 'Ethane03', 'D19ga3r1z', '1994-02-15', 'Male', '0919-621-11293', 'ADD-00003', 'S'),
('USE-00004', 'Mayon Highland Livestock', 'Nicole', 'Fortuna', 'nicolefortunatuna@gmail.com', 'Nicole04', 'E22ca4r8z', '1996-06-30', 'Female', '0920-738-11724', 'ADD-00004', 'S'),
('USE-00005', 'Bicol Verde Agri Farm', 'Gehlee', 'Dangca', 'gehleedangca@gmail.com', 'Gehlee05', 'F35ch2k1z', '1990-11-05', 'Female', '0921-843-12015', 'ADD-00005', 'S'),
('USE-00006', 'Bicol Verde Agri Farm', 'Clarisse', 'Vargas', 'clarissevargas@gmail.com', 'Clarisse06', 'G88bh3n4z', '1992-05-30', 'Female', '0922-951-12546', 'ADD-00006', 'S'),
('USE-00007', 'Bicol Verde Agri Farm', 'Mikee', 'Quintos', 'mikeequintos@gmail.com', 'Mikeeq07', 'H31ch7d9z', '1993-07-27', 'Female', '0923-149-13017', 'ADD-00007', 'S'),
('USE-00008', 'Bicol Verde Agri Farm', 'Raheel', 'Bhyria', 'raheelbhyria@gmail.com', 'Raheel08', 'I34ja8c6z', '1998-09-14', 'Male', '0924-266-13448', 'ADD-00008', 'S'),
('USE-00009', 'Bicol Verde Agri Farm', 'Winwyn', 'Marquez', 'winwynmarquez@gmail.com', 'Winwyn09', 'J37jo9t3z', '1997-12-05', 'Female', '0925-383-13879', 'ADD-00009', 'S');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `capital`
--
ALTER TABLE `capital`
  ADD PRIMARY KEY (`cap_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `livestock`
--
ALTER TABLE `livestock`
  ADD PRIMARY KEY (`livestock_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sales_id`),
  ADD KEY `livestock_id` (`livestock_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `task_schedule`
--
ALTER TABLE `task_schedule`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `address_id` (`address_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `capital`
--
ALTER TABLE `capital`
  ADD CONSTRAINT `capital_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `livestock`
--
ALTER TABLE `livestock`
  ADD CONSTRAINT `livestock_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `owner`
--
ALTER TABLE `owner`
  ADD CONSTRAINT `owner_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `owner_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`livestock_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `task_schedule`
--
ALTER TABLE `task_schedule`
  ADD CONSTRAINT `task_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `address_id` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
