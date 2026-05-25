import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import Login from '../Login';

/**
 * Tests unitaires de la page Login.
 * useAuth est mocké pour isoler le composant de l'API.
 */
vi.mock('../../context/AuthContext', () => ({
  useAuth: vi.fn(),
}));

function renderLogin(loginFn = vi.fn()) {
  useAuth.mockReturnValue({ login: loginFn });
  return render(
    <MemoryRouter>
      <Login />
    </MemoryRouter>
  );
}

describe('Login — rendu', () => {
  beforeEach(() => vi.clearAllMocks());

  it('affiche le champ email', () => {
    renderLogin();
    expect(screen.getByPlaceholderText('vous@exemple.fr')).toBeInTheDocument();
  });

  it('affiche le champ mot de passe', () => {
    renderLogin();
    expect(screen.getByPlaceholderText('••••••••')).toBeInTheDocument();
  });

  it('affiche le bouton de connexion', () => {
    renderLogin();
    expect(screen.getByRole('button', { name: /se connecter/i })).toBeInTheDocument();
  });

  it('affiche le lien mot de passe oublié', () => {
    renderLogin();
    expect(screen.getByText(/mot de passe oublié/i)).toBeInTheDocument();
  });

  it("affiche le lien vers la page d'inscription", () => {
    renderLogin();
    expect(screen.getByText(/s'inscrire/i)).toBeInTheDocument();
  });

  it('affiche le bouton de connexion Google', () => {
    renderLogin();
    expect(screen.getByText(/continuer avec google/i)).toBeInTheDocument();
  });
});

describe('Login — soumission', () => {
  beforeEach(() => vi.clearAllMocks());

  it('appelle login() avec les valeurs du formulaire', async () => {
    const loginFn = vi.fn().mockResolvedValue(undefined);
    renderLogin(loginFn);

    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), {
      target: { value: 'jean@petcare.fr' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'motdepasse123' },
    });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => {
      expect(loginFn).toHaveBeenCalledOnce();
      expect(loginFn).toHaveBeenCalledWith('jean@petcare.fr', 'motdepasse123');
    });
  });

  it("affiche le message d'erreur retourné par l'API", async () => {
    const loginFn = vi.fn().mockRejectedValue({
      response: { data: { message: 'Email ou mot de passe incorrect.' } },
    });
    renderLogin(loginFn);

    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), {
      target: { value: 'bad@petcare.fr' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'faux' },
    });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => {
      expect(screen.getByText('Email ou mot de passe incorrect.')).toBeInTheDocument();
    });
  });

  it("affiche un message d'erreur générique si l'API ne renvoie pas de message", async () => {
    const loginFn = vi.fn().mockRejectedValue(new Error('Network Error'));
    renderLogin(loginFn);

    // Les champs sont required : il faut les remplir pour que le formulaire se soumette
    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), {
      target: { value: 'test@petcare.fr' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'motdepasse' },
    });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => {
      expect(screen.getByText(/email ou mot de passe incorrect/i)).toBeInTheDocument();
    });
  });

  it('désactive le bouton pendant le chargement', async () => {
    // Promise qui ne se résout pas → simule un chargement infini
    const loginFn = vi.fn().mockReturnValue(new Promise(() => {}));
    renderLogin(loginFn);

    fireEvent.change(screen.getByPlaceholderText('vous@exemple.fr'), {
      target: { value: 'jean@petcare.fr' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'motdepasse123' },
    });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /connexion/i })).toBeDisabled();
    });
  });
});
