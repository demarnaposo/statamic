import type { PageProps } from '@inertiajs/core';

export type PortalRoutes = Record<
  | 'home' | 'dashboard' | 'assessments' | 'orders' | 'programs' | 'profile.edit' | 'profile.update'
  | 'password.update' | 'logout' | 'login' | 'register' | 'password.request' | 'password.email'
  | 'password.store' | 'verification.send' | 'login.store' | 'register.store',
  string
>;

export interface SharedProps extends PageProps {
  app: { name: string; landing_url: string };
  auth: { customer: { name: string; email: string; email_verified: boolean } | null };
  flash: { status: string | null };
  routes: PortalRoutes;
  assessment?: { pending: boolean; claimed: boolean };
  errors: Record<string, string>;
}

export type ProgramCard = {
  slug: string;
  title: string;
  summary: string;
  issuer: 'internal' | 'bnsp';
  level: string | null;
  duration: string | null;
  price: number;
  effective_price: number;
  promo_active: boolean;
  next_batch_date: string | null;
  url: string;
};

export type AssessmentResult = {
  id: string;
  version: number;
  recommended_category: { slug: string; title: string };
  scores: { slug: string; title: string; score: number }[];
  taken_at: string;
};
