import express from 'express';
import {
    getAllReportCategories,
    createReportCategory,
    updateReportCategory,
    deleteReportCategory,
    getCategoriesByRole
} from '../controllers/ReportCategoryController';
import { authenticateToken } from '../middleware/auth';

const router = express.Router();

// Alle Routen erfordern Authentifizierung
router.use(authenticateToken);

// Basis-Routen
router.get('/', getAllReportCategories);
router.post('/', createReportCategory);
router.put('/:id', updateReportCategory);
router.delete('/:id', deleteReportCategory);

// Neue Route für rollenspezifische Kategorien
router.get('/by-role/:roleId', getCategoriesByRole);

export default router; 