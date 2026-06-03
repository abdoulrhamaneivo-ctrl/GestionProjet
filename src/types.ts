export type UserRole = 'etudiant' | 'prof' | 'admin';

export interface DbUser {
  id_user: number;
  nom: string;
  prenom: string;
  email: string;
  role: UserRole;
}

export interface DbExercise {
  id_exercice: number;
  titre: string;
  type_exercice: 'individuel' | 'groupe';
  date_limite: string;
  est_bloque: boolean;
}

export interface DbTheme {
  id_theme: number;
  id_exercice: number;
  nom_theme: string;
}

export interface DbGroup {
  id_groupe: number;
  id_exercice: number;
  id_chef: number;
  nom_groupe: string;
}

export interface DbMember {
  id_liaison: number;
  id_groupe: number;
  id_user: number;
}

export interface DbProject {
  id_projet: number;
  id_exercice: number;
  id_theme: number | null;
  id_user_individuel: number | null;
  id_groupe: number | null;
  titre_projet: string;
  lien_url: string;
  acces_test: string;
  explications: string;
  cahier_charges_path: string | null;
  note_design: number | null;
  note_code: number | null;
  note_fonc: number | null;
  note_totale: number | null;
  critique_prof: string | null;
  notes_publiees: boolean;
  statut_public: boolean;
  date_soumission: string;
}

export interface DbComment {
  id_comment: number;
  id_projet: number;
  id_user: number;
  pseudonyme: string;
  contenu: string;
  date_publication: string;
}

export interface DbDerogation {
  id_derogation: number;
  id_exercice: number;
  id_user: number | null;
  id_groupe: number | null;
  nouvelle_date: string;
}

// PHP routing logs for the PRG visualizer
export interface PrgLog {
  timestamp: string;
  method: 'GET' | 'POST' | '302 REDIRECT';
  uri: string;
  payload?: any;
  flashMsg?: { type: 'success' | 'error'; text: string };
  statusCode: number;
}
