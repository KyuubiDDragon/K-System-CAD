export interface Folder {
    id: number;
    name: string;
    // Add any other properties your folders might have
}

export interface File {
    id: number;
    name: string;
    path: string;
    size: number;
    // Add any other properties your files might have
}
