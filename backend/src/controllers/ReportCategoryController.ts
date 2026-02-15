import { Request, Response } from 'express';
import ReportCategory from '../models/ReportCategory';
import UserRole from '../models/UserRole';

export const getAllReportCategories = async (req: Request, res: Response) => {
    try {
        const categories = await ReportCategory.findAll({
            include: [{
                model: UserRole,
                as: 'allowedRoles',
                attributes: ['id', 'name']
            }],
            order: [['order', 'ASC']]
        });
        res.json(categories);
    } catch (error) {
        res.status(500).json({ message: 'Error fetching report categories', error });
    }
};

export const createReportCategory = async (req: Request, res: Response) => {
    try {
        const { name, description, color, icon, order, active, allowedRoleIds } = req.body;
        
        const category = await ReportCategory.create({
            name,
            description,
            color,
            icon,
            order,
            active
        });

        if (allowedRoleIds && Array.isArray(allowedRoleIds)) {
            await category.setAllowedRoles(allowedRoleIds);
        }

        const createdCategory = await ReportCategory.findByPk(category.id, {
            include: [{
                model: UserRole,
                as: 'allowedRoles',
                attributes: ['id', 'name']
            }]
        });

        res.status(201).json(createdCategory);
    } catch (error) {
        res.status(500).json({ message: 'Error creating report category', error });
    }
};

export const updateReportCategory = async (req: Request, res: Response) => {
    try {
        const { id } = req.params;
        const { name, description, color, icon, order, active, allowedRoleIds } = req.body;

        const category = await ReportCategory.findByPk(id);
        if (!category) {
            return res.status(404).json({ message: 'Report category not found' });
        }

        await category.update({
            name,
            description,
            color,
            icon,
            order,
            active
        });

        if (allowedRoleIds && Array.isArray(allowedRoleIds)) {
            await category.setAllowedRoles(allowedRoleIds);
        }

        const updatedCategory = await ReportCategory.findByPk(id, {
            include: [{
                model: UserRole,
                as: 'allowedRoles',
                attributes: ['id', 'name']
            }]
        });

        res.json(updatedCategory);
    } catch (error) {
        res.status(500).json({ message: 'Error updating report category', error });
    }
};

export const deleteReportCategory = async (req: Request, res: Response) => {
    try {
        const { id } = req.params;
        const category = await ReportCategory.findByPk(id);
        
        if (!category) {
            return res.status(404).json({ message: 'Report category not found' });
        }

        await category.destroy();
        res.json({ message: 'Report category deleted successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Error deleting report category', error });
    }
};

// New function to get categories accessible by a specific user role
export const getCategoriesByRole = async (req: Request, res: Response) => {
    try {
        const { roleId } = req.params;
        const categories = await ReportCategory.findAll({
            include: [{
                model: UserRole,
                as: 'allowedRoles',
                where: { id: roleId },
                attributes: ['id', 'name']
            }],
            order: [['order', 'ASC']]
        });
        res.json(categories);
    } catch (error) {
        res.status(500).json({ message: 'Error fetching categories for role', error });
    }
}; 