export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'waived'
export type PaymentMethod = 'cash' | 'airtel_money' | 'mtn_momo' | 'bank_transfer' | 'cheque'
export type ExpenseCategory = 'salaries' | 'utilities' | 'maintenance' | 'supplies' | 'transport' | 'feeding' | 'library' | 'other'
export type FeeAppliesTo = 'all' | 'day_scholars' | 'boarders' | 'new_pupils'
export type IncomeSource = 'donation' | 'grant' | 'uniform_sales' | 'book_sales' | 'feeding' | 'rental' | 'fundraising' | 'other'

export interface OtherIncome {
    id: number
    school_id: number
    source: IncomeSource
    description: string
    amount: number
    received_date: string
    recorded_by: number | null
    reference: string | null
    media?: Array<{ id: number; original_url: string; file_name: string }>
}

export interface FeeStructure {
    id: number
    school_id: number
    grade_id: number | null
    term_id: number
    academic_year_id: number
    name: string
    description: string | null
    amount: number
    applies_to: FeeAppliesTo
    is_mandatory: boolean
    grade?: { id: number; grade_number: number }
    term?: { id: number; name: string }
    academic_year?: { id: number; name: string }
}

export interface FeeInvoice {
    id: number
    school_id: number
    pupil_id: number
    fee_structure_id: number
    term_id: number
    academic_year_id: number
    amount: number
    discount: number
    balance_due: number
    due_date: string | null
    status: InvoiceStatus
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string; grade?: { grade_number: number } }
    fee_structure?: { id: number; name: string }
    term?: { id: number; name: string }
    payments?: FeePayment[]
}

export interface FeePayment {
    id: number
    school_id: number
    pupil_id: number
    invoice_id: number
    amount: number
    payment_method: PaymentMethod
    reference: string | null
    transaction_id: string | null
    mobile_money_provider: string | null
    received_by: number
    payment_date: string
    received_by_user?: { id: number; name: string }
}

export interface Expense {
    id: number
    school_id: number
    category: ExpenseCategory
    description: string
    amount: number
    expense_date: string
    approved_by: number | null
    receipt_no: string | null
    media?: Array<{ id: number; original_url: string; file_name: string }>
}

export interface Budget {
    id: number
    school_id: number
    academic_year_id: number
    term_id: number | null
    category: string
    amount: number
    academic_year?: { id: number; name: string }
    term?: { id: number; name: string } | null
}

export interface BudgetVsActualItem {
    category: string
    budget: number
    actual: number
    variance: number
}

export interface AgingBucket {
    key: string
    label: string
    amount: number
    count: number
}

export interface Debtor {
    pupil_id: number
    name: string
    admission_no: string
    grade: string | number
    outstanding: number
    invoice_count: number
    oldest_due_date: string | null
}

export interface ReceivablesAging {
    as_of: string
    buckets: AgingBucket[]
    total_outstanding: number
    total_count: number
    debtors: Debtor[]
}

export interface ProfitLoss {
    from: string
    to: string
    fees_collected: number
    other_income_total: number
    other_income_by_source: Array<{ source: string; amount: number }>
    total_income: number
    expenses_by_category: Array<{ category: string; amount: number }>
    total_expenses: number
    net: number
}

export interface FeeStatement {
    invoices: FeeInvoice[]
    payments: FeePayment[]
    total_due: number
    total_paid: number
    outstanding: number
}

export interface FeeReport {
    total_invoiced: number
    total_collected: number
    outstanding: number
    collection_rate_pct: number
    by_grade: Array<{
        grade: string | number
        invoiced: number
        collected: number
        outstanding: number
        collection_pct: number
    }>
}
