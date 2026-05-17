export interface SalaryAdjustment {
    id?: number;
    user_id: number;
    amount: number;
    reason: string;
    adjustment_date: string;
    user?: {
        id: number;
        name: string;
        code: string;
    };
}
