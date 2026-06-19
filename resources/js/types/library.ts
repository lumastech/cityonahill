export type BorrowerType = 'pupil' | 'staff'
export type BorrowingStatus = 'borrowed' | 'returned' | 'overdue' | 'lost'

export interface LibraryBook {
    id: number
    school_id: number
    title: string
    author: string
    isbn: string | null
    publisher: string | null
    publish_year: number | null
    category: string
    subject_id: number | null
    copies_total: number
    copies_available: number
    shelf_location: string | null
    description: string | null
    is_available: boolean
    subject?: { id: number; name: string }
    borrowings?: BookBorrowing[]
    media?: Array<{ id: number; original_url: string; file_name: string }>
}

export interface BookBorrowing {
    id: number
    school_id: number
    book_id: number
    borrower_type: BorrowerType
    borrower_id: number
    borrowed_date: string
    due_date: string
    returned_date: string | null
    fine_amount: number
    status: BorrowingStatus
    issued_by: number
    returned_to: number | null
    book?: Pick<LibraryBook, 'id' | 'title' | 'author' | 'category'>
    issued_by_user?: { id: number; name: string }
}

export interface BorrowerSummary {
    borrower_type: BorrowerType
    borrower_id: number
    borrower_name: string
    active_count: number
    overdue_count: number
    total_fines: number
}
