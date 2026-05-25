import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import Register from '../Register';

/**
 * Tests unitaires de la page Register.
 * Le hook useAuth est mocké pour isoler le composant de l'API.
 */
vi.mock('../../context/AuthContext', () => ({
  useAuth: vi.fn(),
}));

import { useAuth } from '../../context/AuthContext';

function renderRegister(registerFn = vi.fn()) {
  vi.mocked(useAuth).mockReturnValue({ register: registerFn });
  return render(
    <MemoryRouter>
      <Register />
    </MemoryRouter>
  );
}

describe('Register — rendu', () => {
  it('affiche les champs du formulaire', () => {
    renderRegister();
    expect(screen.getByPlaceholderText('Jean')).toBeInTheDocument();     // prénom
    expect(screen.getByPlaceholderText('Dupont')).toBeInTheDocument();  // nom
    expect(screen.getByPlaceholderText('vous@exemple.fr')).toBeInTheDocument();
    // deux champs password (2× "••••••••")
    expect(screen.getAllByPlaceholderText('••••••••').length).toBeGreaterThanOrEqual(1);
  });

  it('affiche le bouton de création de compte', () => {
    renderRegister();
    expect(screen.getByRole('button', { name: /créer mon compte/i })).toBeInTheDocument();
  });

  it("affiche le lien vers la page de connexion", () => {
    renderRegister();
    expect(screen.getByText(/se connecter/i)).toBeInTheDocument();
  });

  it("affiche le bouton d'inscription Google", () => {
    renderRegister();
    expect(screen.getByText(/s'inscrire avec google/i)).toBeInTheDocument();
  });
});

describe('Register — validation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('affiche une erreur si les mots de passe ne correspondent pas', async () => {
    renderRegister();

    fireEvent.change(screen.getByPlaceholderText('Jean'), { target: { value: 'Jean' } });
    fireEvent.change(screen.getByPlaceholderText('Dupont'), { target: { value: 'Dupont' } });
    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), { target: { value: 'jean@petcare.fr' } });

    const [passwordInput, confirmInput] = screen.getAllByPlaceholderText('••••••••');
    fireEvent.change(passwordInput, { target: { value: 'motdepasse123' } });
    fireEvent.change(confirmInput, { target: { value: 'different456' } });

    fireEvent.click(screen.getByRole('button', { name: /créer mon compte/i }));

    await waitFor(() => {
      expect(screen.getByText(/mots de passe ne correspondent pas/i)).toBeInTheDocument();
    });
  });

  it('affiche une erreur si le mot de passe est trop court', async () => {
    renderRegister();

    fireEvent.change(screen.getByPlaceholderText('Jean'), { target: { value: 'Jean' } });
    fireEvent.change(screen.getByPlaceholderText('Dupont'), { target: { value: 'Dupont' } });
    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), { target: { value: 'jean@petcare.fr' } });

    const [passwordInput, confirmInput] = screen.getAllByPlaceholderText('••••••••');
    fireEvent.change(passwordInput, { target: { value: 'court' } });
    fireEvent.change(confirmInput, { target: { value: 'court' } });

    fireEvent.click(screen.getByRole('button', { name: /créer mon compte/i }));

    await waitFor(() => {
      expect(screen.getByText(/au moins 8 caractères/i)).toBeInTheDocument();
    });
  });

  it('ne valide pas les mots de passe avant la soumission', () => {
    renderRegister();
    // Aucun message d'erreur visible avant soumission
    expect(screen.queryByText(/mots de passe/i)).not.toBeInTheDocument();
  });
});

describe('Register — soumission', () => {
  it('appelle register() avec les bonnes données', async () => {
    const registerFn = vi.fn().mockResolvedValue(undefined);
    renderRegister(registerFn);

    fireEvent.change(screen.getByPlaceholderText('Jean'), { target: { value: 'Jean' } });
    fireEvent.change(screen.getByPlaceholderText('Dupont'), { target: { value: 'Dupont' } });
    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), { target: { value: 'jean@petcare.fr' } });

    const [passwordInput, confirmInput] = screen.getAllByPlaceholderText('••••••••');
    fireEvent.change(passwordInput, { target: { value: 'motdepasse123' } });
    fireEvent.change(confirmInput, { target: { value: 'motdepasse123' } });

    fireEvent.click(screen.getByRole('button', { name: /créer mon compte/i }));

    await waitFor(() => {
      expect(registerFn).toHaveBeenCalledOnce();
      expect(registerFn).toHaveBeenCalledWith('Dupont', 'Jean', 'jean@petcare.fr', 'motdepasse123');
    });
  });

  it("affiche le message d'erreur retourné par l'API", async () => {
    const registerFn = vi.fn().mockRejectedValue({
      response: { data: { message: 'Cet email est déjà utilisé.' } },
    });
    renderRegister(registerFn);

    fireEvent.change(screen.getByPlaceholderText('Jean'), { target: { value: 'Jean' } });
    fireEvent.change(screen.getByPlaceholderText('Dupont'), { target: { value: 'Dupont' } });
    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), { target: { value: 'dupe@petcare.fr' } });

    const [passwordInput, confirmInput] = screen.getAllByPlaceholderText('••••••••');
    fireEvent.change(passwordInput, { target: { value: 'motdepasse123' } });
    fireEvent.change(confirmInput, { target: { value: 'motdepasse123' } });

    fireEvent.click(screen.getByRole('button', { name: /créer mon compte/i }));

    await waitFor(() => {
      expect(screen.getByText('Cet email est déjà utilisé.')).toBeInTheDocument();
    });
  });
});
