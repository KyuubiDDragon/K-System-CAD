import * as L from 'leaflet';
export interface Categories {
    id: number;
    name: string;
    icon: string;
    marker_count?: number;
}

export interface Markers{
    id: number;
    name: string;
    ceo: string;
    phonenumber: string;
    location: string;
    category_id: number;
    x_coordinate: number;
    y_coordinate: number;
    icon: string;
}

export interface MarkerLayer extends L.Layer {
    name: string;
    location: string;
    ceo: string;
    phonenumber: string;
    category_id: number;
    x_coordinate: number;
    y_coordinate: number;
    icon: string;
  }
  