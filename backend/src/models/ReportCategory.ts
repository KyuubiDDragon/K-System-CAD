import { Model, DataTypes } from 'sequelize';
import sequelize from '../config/database';
import UserRole from './UserRole';

class ReportCategory extends Model {
    public id!: number;
    public name!: string;
    public description?: string;
    public color?: string;
    public icon?: string;
    public order?: number;
    public active!: boolean;
    public readonly createdAt!: Date;
    public readonly updatedAt!: Date;
}

ReportCategory.init(
    {
        id: {
            type: DataTypes.INTEGER,
            autoIncrement: true,
            primaryKey: true,
        },
        name: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        description: {
            type: DataTypes.TEXT,
            allowNull: true,
        },
        color: {
            type: DataTypes.STRING,
            allowNull: true,
        },
        icon: {
            type: DataTypes.STRING,
            allowNull: true,
        },
        order: {
            type: DataTypes.INTEGER,
            allowNull: true,
        },
        active: {
            type: DataTypes.BOOLEAN,
            allowNull: false,
            defaultValue: true,
        },
    },
    {
        sequelize,
        tableName: 'kdd_report_categories',
    }
);

// Define the many-to-many relationship
ReportCategory.belongsToMany(UserRole, {
    through: 'kdd_report_category_user_roles',
    foreignKey: 'report_category_id',
    otherKey: 'user_role_id',
    as: 'allowedRoles'
});

UserRole.belongsToMany(ReportCategory, {
    through: 'kdd_report_category_user_roles',
    foreignKey: 'user_role_id',
    otherKey: 'report_category_id',
    as: 'accessibleCategories'
});

export default ReportCategory; 