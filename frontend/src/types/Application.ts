import { Application_Type } from '@/enums/Application'
export interface Applicant {
    id: number;
    newApplication:boolean;
    name: string;
    birthdate: string;
    email: string;
    status: string;
    info: string;
    type: Application_Type;
    jobinterviewDate: string;
    jobinterviewTime: string;
    phonenumber: string;
    addToCalendar: boolean;
    added: string;
    answers: { 
        applicant_id: number; 
        question_id: number; 
        question: string; 
        answer: string; 
    }[];
}
export interface ApplicantPending{
    id: number;
    name: string;
    email: string;
    status: string;
    info: string;
    jobinterviewDate: string;
    jobinterviewTime: string;
    phonenumber: string;
}

export interface Question {
    id: number;
    question: string;
    type: Application_Type; 
    sort_order: number;
}

export interface ApplicationData {
    newApplication:boolean;
    name:string;
    id:number;
    type:number;
    email: string;
    birthdate: string;
    status: number | string;
    info: string;
    jobinterviewDate: string;
    jobinterviewTime: string;
    phonenumber: string;
    addToCalendar: boolean;
    added: string;
    answers: { 
        applicant_id: number; 
        question_id: number; 
        question: string; 
        answer: string; 
    }[];
}
  