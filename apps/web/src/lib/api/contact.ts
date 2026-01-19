const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export interface ContactFormData {
  name: string;
  email: string;
  message: string;
}

export interface ContactFormResponse {
  success: boolean;
  message: string;
}

/**
 * Submit contact form to the API
 */
export async function submitContactForm(data: ContactFormData): Promise<ContactFormResponse> {
  const response = await fetch(`${API_URL}/contact`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: 'An error occurred while submitting the form',
    }));
    throw new Error(error.message || 'Failed to submit contact form');
  }

  return response.json();
}
