export type MealType = 'breakfast' | 'lunch' | 'snack'
export type StockMovementType = 'restock' | 'consumption' | 'wastage' | 'adjustment'

export interface FeedingSession {
    id: number
    school_id: number
    date: string
    meal_type: MealType
    stream_id: number | null
    recorded_by: number
    finalized: boolean
    served_count?: number
    stream?: { id: number; name: string; grade?: { id: number; name: string } }
    created_at: string
    updated_at: string
}

export interface FeedingRecord {
    id: number
    session_id: number
    pupil_id: number
    served: boolean
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string }
}

export interface FeedingStock {
    id: number
    school_id: number
    item_name: string
    unit: string
    quantity_on_hand: number
    reorder_level: number
    last_restocked_at: string | null
    cost_per_unit: number | null
    created_at: string
    updated_at: string
}

export interface FeedingStockMovement {
    id: number
    school_id: number
    stock_id: number
    type: StockMovementType
    quantity: number
    notes: string | null
    recorded_by: number
    created_at: string
}

export interface FeedingStats {
    total_served: number
    by_day: { date: string; meals: number }[]
}
