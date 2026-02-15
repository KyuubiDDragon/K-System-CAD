export interface InvoiceItem {
    id: number;
    invoice_id: number;
    item_name: string;
    description: string;
    price: number;
    quantity: number;
  }

  export interface Invoice {
    id: number;
    title: string;
    customer?: string;
    is_sent: boolean;
    is_paid: boolean;
    is_delivered: boolean;
    outgoing: boolean;
    is_sent_date: string | null;
    is_paid_date: string | null;
    is_delivered_date: string | null;
    items: InvoiceItem[];
    [key: string]: any; // For any additional properties
  }