// src/lib/config.ts
import { PUBLIC_API_BASE_URL, PUBLIC_STORAGE_BASE_URL } from '$env/static/public';

// Fallback kecil supaya tetap jalan bila env belum diset (opsional)
export const API_BASE_URL = PUBLIC_API_BASE_URL || 'http://localhost:8000/api';
export const STORAGE_BASE_URL = PUBLIC_STORAGE_BASE_URL || 'http://localhost:8000/storage';
