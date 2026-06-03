import React, { useState, useEffect } from 'react';
import { 
  Database as DbIcon, 
  Code as CodeIcon, 
  Terminal as TerminalIcon, 
  Users, 
  User as UserIcon, 
  Plus, 
  CheckCircle, 
  AlertCircle, 
  ExternalLink, 
  Download, 
  FileText, 
  Folder, 
  FolderOpen, 
  Copy, 
  Check, 
  Lock, 
  Calendar, 
  MessageSquare, 
  Send, 
  BookOpen, 
  ShieldCheck, 
  Layout, 
  Activity, 
  ArrowRight,
  TrendingUp,
  Award
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { phpCodeFiles, FileCode } from './phpCodeData';
import { 
  DbUser, 
  DbExercise, 
  DbTheme, 
  DbGroup, 
  DbProject, 
  DbComment, 
  DbDerogation, 
  PrgLog 
} from './types';

// ==========================================
// INITIAL SEED DATABASE STATE (Mocks local PHP database)
// ==========================================

const initialUsers: DbUser[] = [
  { id_user: 1, nom: 'Admin', prenom: 'Ivob', email: 'admin@emsp.ci', role: 'admin' },
  { id_user: 2, nom: 'Amadou', prenom: 'M.', email: 'amadou@emsp.ci', role: 'prof' },
  { id_user: 3, nom: 'Koffi', prenom: 'Jean', email: 'jean.koffi@emsp.ci', role: 'etudiant' },
  { id_user: 4, nom: 'Traore', prenom: 'Fatoumata', email: 'fatou.traore@emsp.ci', role: 'etudiant' },
  { id_user: 5, nom: 'Diallo', prenom: 'Mamadou', email: 'mamadou.diallo@emsp.ci', role: 'etudiant' },
  { id_user: 6, nom: 'Bamba', prenom: 'Bakary', email: 'bakary.bamba@emsp.ci', role: 'etudiant' },
  { id_user: 7, nom: 'Gomez', prenom: 'Marie', email: 'marie.gomez@emsp.ci', role: 'etudiant' }
];

const initialExercises: DbExercise[] = [
  { id_exercice: 1, titre: 'Projet Conception d\'Application Web', type_exercice: 'groupe', date_limite: '2026-06-08T23:59', est_bloque: false },
  { id_exercice: 2, titre: 'Mini-Projet PHP POO/MVC Individuel', type_exercice: 'individuel', date_limite: '2026-05-31T23:59', est_bloque: false }
];

const initialThemes: DbTheme[] = [
  { id_theme: 1, id_exercice: 1, nom_theme: 'Plateforme E-Commerce EMSP' },
  { id_theme: 2, id_exercice: 1, nom_theme: 'Système de gestion de stock de matériel' },
  { id_theme: 3, id_exercice: 1, nom_theme: 'Annuaire numérique des étudiants DSER' },
  { id_theme: 4, id_exercice: 1, nom_theme: 'Portail de vote électronique du club informatique' },
  { id_theme: 5, id_exercice: 2, nom_theme: 'Générateur de fiches d\'évaluation PDF' },
  { id_theme: 6, id_exercice: 2, nom_theme: 'Raccourcisseur d\'URL sécurisé' }
];

const initialGroups: DbGroup[] = [
  { id_groupe: 1, id_exercice: 1, id_chef: 4, nom_groupe: 'DSER Club Info Group' }
];

const initialMembers = [
  { id_liaison: 1, id_groupe: 1, id_user: 4 }, // Fatou Traore is Chef of Group 1
  { id_liaison: 2, id_groupe: 1, id_user: 3 }  // Jean Koffi is Member of Group 1
];

const initialProjects: DbProject[] = [
  {
    id_projet: 1,
    id_exercice: 1,
    id_theme: 3,
    id_user_individuel: null,
    id_groupe: 1,
    titre_projet: 'EMSP Student Hub v1',
    lien_url: 'https://emsp-hub.run.app',
    acces_test: 'admin / admin123, prof / prof123',
    explications: 'Notre application implémente le routage MVC, la validation stricte de Type MIME, et s\'articule autour de trois rôles distincts pour un suivi académique transparent.',
    cahier_charges_path: 'uploads/cdc_1_traore_fatoumata_6e5b4.pdf',
    note_design: null,
    note_code: null,
    note_fonc: null,
    note_totale: null,
    critique_prof: null,
    notes_publiees: false,
    statut_public: false,
    date_soumission: '2026-06-01T14:32'
  },
  {
    id_projet: 2,
    id_exercice: 2,
    id_theme: 6,
    id_user_individuel: 5, // Mamadou Diallo submitted individual project (LATE)
    id_groupe: null,
    titre_projet: 'Secure URL Shorter',
    lien_url: 'https://shorter.ci',
    acces_test: 'testuser / testpass',
    explications: 'Un service de réduction d\'URL performant avec hachage sha256 et filtrage IP contre le spam.',
    cahier_charges_path: 'uploads/cdc_2_diallo_mamadou_1f2e4.pdf',
    note_design: 4.5,
    note_code: 4.25,
    note_fonc: 8.5,
    note_totale: 17.25,
    critique_prof: 'Excellent travail, très robuste. L\'utilisation du pattern MVC et de requêtes préparées PDO est parfaitement maîtrisée. Faites attention aux indices SQL dans phpMyAdmin.',
    notes_publiees: true,
    statut_public: true, // Visible in peer gallery
    date_soumission: '2026-06-01T09:12' // Submitted on June 1st, while deadline is May 31 => LATE
  }
];

const initialComments: DbComment[] = [
  { id_comment: 1, id_projet: 2, id_user: 4, pseudonyme: 'Étudiant_ab4f', contenu: 'Super interface, très minimaliste ! J\'ai pu tester sur mon mobile localement, tout s\'adapte à merveille. Quel framework CSS as-tu utilisé ?', date_publication: '2026-06-01T15:20' }
];

const initialDerogations: DbDerogation[] = [];

// ==========================================
// CORE APP COMPONENT
// ==========================================

export default function App() {
  const [activeTab, setActiveTab] = useState<'simulation' | 'database' | 'code'>('simulation');

  // Database States
  const [users, setUsers] = useState<DbUser[]>(initialUsers);
  const [exercises, setExercises] = useState<DbExercise[]>(initialExercises);
  const [themes, setThemes] = useState<DbTheme[]>(initialThemes);
  const [groups, setGroups] = useState<DbGroup[]>(initialGroups);
  const [members, setMembers] = useState(initialMembers);
  const [projects, setProjects] = useState<DbProject[]>(initialProjects);
  const [comments, setComments] = useState<DbComment[]>(initialComments);
  const [derogations, setDerogations] = useState<DbDerogation[]>(initialDerogations);

  // Simulation Session States (Client-side Cookie Mock)
  const [loggedUser, setLoggedUser] = useState<DbUser | null>(null);
  const [flashMessage, setFlashMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // PRG Console logs state
  const [prgLogs, setPrgLogs] = useState<PrgLog[]>([
    { timestamp: '20:38:04', method: 'GET', uri: '/', statusCode: 200 }
  ]);

  // Code Viewer States
  const [selectedFile, setSelectedFile] = useState<FileCode>(phpCodeFiles[0]);
  const [searchQuery, setSearchQuery] = useState('');
  const [copiedFile, setCopiedFile] = useState<string | null>(null);

  // Active form selectors
  const [currentExSelect, setCurrentExSelect] = useState<number | null>(null);
  const [submitFormTitle, setSubmitFormTitle] = useState('');
  const [submitFormUrl, setSubmitFormUrl] = useState('');
  const [submitFormAcces, setSubmitFormAcces] = useState('');
  const [submitFormExplications, setSubmitFormExplications] = useState('');
  const [submitFormTheme, setSubmitFormTheme] = useState<number | null>(null);
  const [submitFormFile, setSubmitFormFile] = useState<string | null>(null);
  const [submitFormIsGroup, setSubmitFormIsGroup] = useState(false);
  const [submitFormGroupName, setSubmitFormGroupName] = useState('');
  const [submitFormGroupMembers, setSubmitFormGroupMembers] = useState<number[]>([]);

  // Professor Evaluation Form states
  const [gradingProject, setGradingProject] = useState<DbProject | null>(null);
  const [gradeDesign, setGradeDesign] = useState(4.00);
  const [gradeCode, setGradeCode] = useState(4.00);
  const [gradeFonc, setGradeFonc] = useState(8.00);
  const [gradeCritique, setGradeCritique] = useState('');
  const [gradePublic, setGradePublic] = useState(false);

  // Admin New Exercise Panel
  const [adminExTitle, setAdminExTitle] = useState('');
  const [adminExType, setAdminExType] = useState<'individuel' | 'groupe'>('individuel');
  const [adminExDeadline, setAdminExDeadline] = useState('2026-06-15T23:59');
  const [adminExThemes, setAdminExThemes] = useState('');

  // Admin exemptions
  const [adminExempUser, setAdminExempUser] = useState<number | null>(null);
  const [adminExempGroup, setAdminExempGroup] = useState<number | null>(null);
  const [adminExempDate, setAdminExempDate] = useState('2026-06-20T23:59');
  const [adminExempExercice, setAdminExempExercice] = useState<number>(1);

  // Peer testing list input helper
  const [peerCommentText, setPeerCommentText] = useState<{ [projectId: number]: string }>({});

  // Trigger temporary flash messages
  const triggerFlash = (type: 'success' | 'error', text: string) => {
    setFlashMessage({ type, text });
    setTimeout(() => {
      setFlashMessage(null);
    }, 5000);
  };

  // Log PRG events helper
  const appendPrgLog = (method: 'GET' | 'POST' | '302 REDIRECT', uri: string, payload?: any, flash?: { type: 'success' | 'error'; text: string }) => {
    const time = new Date().toTimeString().split(' ')[0];
    setPrgLogs(prev => [
      { timestamp: time, method, uri, payload, flashMsg: flash, statusCode: method === 'GET' ? 200 : method === 'POST' ? 302 : 302 },
      ...prev
    ]);
  };

  // Helper: check deadlines with custom derogations
  const getSubmissionDeadlineStatus = (exId: number, userId: number) => {
    const ex = exercises.find(e => e.id_exercice === exId);
    if (!ex) return { allowed: false, isLate: false, deadline: '' };

    if (ex.est_bloque) return { allowed: false, isLate: false, deadline: '' };

    let deadlineVal = ex.date_limite;
    
    // Check if user belongs to group for this ex
    const groupOfUser = groups.find(g => {
      if (g.id_exercice !== exId) return false;
      if (g.id_chef === userId) return true;
      const mems = members.filter(m => m.id_groupe === g.id_groupe).map(m => m.id_user);
      return mems.includes(userId);
    });

    const dero = derogations.find(d => {
      if (d.id_exercice !== exId) return false;
      if (d.id_user === userId) return true;
      if (groupOfUser && d.id_groupe === groupOfUser.id_groupe) return true;
      return false;
    });

    if (dero) {
      deadlineVal = dero.nouvelle_date;
    }

    const now = Date.now(); // Utiliser l'heure réelle pour détecter les retards
    const lmt = new Date(deadlineVal).getTime();
    const isLate = now > lmt;

    return {
      allowed: true,
      isLate,
      deadlineUrl: deadlineVal,
      originalStr: ex.date_limite
    };
  };

  // Copy code helper
  const handleCopyCode = (file: FileCode) => {
    navigator.clipboard.writeText(file.content).then(() => {
      setCopiedFile(file.path);
    }).catch(() => {
      // Fallback si l'API clipboard n'est pas disponible
      setCopiedFile(null);
    });
    return;
    setTimeout(() => {
      setCopiedFile(null);
    }, 2000);
  };

  // Filter files in workspace tree
  const filteredFiles = phpCodeFiles.filter(f => 
    f.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    f.path.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // ==========================================
  // ACTION HANDLERS : SIMULATED PHP POST ACTIONS WITH PRG ROUTING
  // ==========================================

  const handleLoginSubmit = (e: React.FormEvent, email: string) => {
    e.preventDefault();
    appendPrgLog('POST', '/auth/login', { email, password: 'password123' });

    const user = users.find(u => u.email === email);
    if (!user) {
      appendPrgLog('302 REDIRECT', '/auth/login', null, { type: 'error', text: 'Identifiants de connexion incorrects.' });
      appendPrgLog('GET', '/auth/login', null);
      triggerFlash('error', "Identifiants sélectifs erronés.");
      return;
    }

    // PRG Success Redirection simulation
    setTimeout(() => {
      appendPrgLog('302 REDIRECT', `/${user.role}/dashboard`, { session: 'authenticated' }, { type: 'success', text: 'Connexion établie avec succès !' });
      setLoggedUser(user);
      triggerFlash('success', `Ravi de vous voir, ${user.prenom} ! Connexion réussie.`);
      
      setTimeout(() => {
        appendPrgLog('GET', `/student/dashboard`, null);
      }, 200);
    }, 300);
  };

  const handleLogout = () => {
    appendPrgLog('GET', '/auth/logout', null);
    setLoggedUser(null);
    appendPrgLog('302 REDIRECT', '/auth/login', null, { type: 'success', text: 'Déconnecté avec succès.' });
    appendPrgLog('GET', '/auth/login', null);
    triggerFlash('success', 'Session déconnectée avec succès du hub PHP.');
  };

  // Submit project PHP code action simulator
  const handleProjectSubmitAction = (e: React.FormEvent) => {
    e.preventDefault();
    if (!loggedUser || !currentExSelect) return;

    appendPrgLog('POST', '/student/submit', {
      id_exercice: currentExSelect,
      id_theme: submitFormTheme,
      titre_projet: submitFormTitle,
      lien_url: submitFormUrl,
      acces_test: submitFormAcces,
      explications: submitFormExplications,
      is_group: submitFormIsGroup,
      nom_groupe: submitFormGroupName,
      members: submitFormGroupMembers
    });

    try {
      let gId: number | null = null;
      let existingGroup = groups.find(g => g.id_exercice === currentExSelect && (g.id_chef === loggedUser.id_user || members.some(m => m.id_groupe === g.id_groupe && m.id_user === loggedUser.id_user)));

      if (submitFormIsGroup && !existingGroup) {
        // Create group first
        const newGroup: DbGroup = {
          id_groupe: groups.length + 1,
          id_exercice: currentExSelect,
          id_chef: loggedUser.id_user,
          nom_groupe: submitFormGroupName || `Groupe de ${loggedUser.nom}`
        };
        setGroups(prev => [...prev, newGroup]);
        gId = newGroup.id_groupe;

        // Associate members
        const newLiaisons = [
          { id_liaison: members.length + 1, id_groupe: newGroup.id_groupe, id_user: loggedUser.id_user },
          ...submitFormGroupMembers.map((mid, idx) => ({
            id_liaison: members.length + idx + 2,
            id_groupe: newGroup.id_groupe,
            id_user: Number(mid)
          }))
        ];
        setMembers(prev => [...prev, ...newLiaisons]);
      } else if (existingGroup) {
        gId = existingGroup.id_groupe;
      }

      // Check for existing submissions
      const existingProjIdx = projects.findIndex(p => p.id_exercice === currentExSelect && (p.id_user_individuel === loggedUser.id_user || (gId && p.id_groupe === gId)));

      if (existingProjIdx !== -1) {
        // Update model
        const updated = [...projects];
        updated[existingProjIdx] = {
          ...updated[existingProjIdx],
          id_theme: submitFormTheme,
          titre_projet: submitFormTitle,
          lien_url: submitFormUrl,
          acces_test: submitFormAcces,
          explications: submitFormExplications,
          cahier_charges_path: submitFormFile || updated[existingProjIdx].cahier_charges_path,
          date_soumission: new Date().toISOString().slice(0, 16)
        };
        setProjects(updated);
      } else {
        // Create model
        const newProj: DbProject = {
          id_projet: projects.length + 1,
          id_exercice: currentExSelect,
          id_theme: submitFormTheme,
          id_user_individuel: submitFormIsGroup ? null : loggedUser.id_user,
          id_groupe: gId,
          titre_projet: submitFormTitle,
          lien_url: submitFormUrl,
          acces_test: submitFormAcces,
          explications: submitFormExplications,
          cahier_charges_path: submitFormFile || 'uploads/sample_cahier_charges.pdf',
          note_design: null,
          note_code: null,
          note_fonc: null,
          note_totale: null,
          critique_prof: null,
          notes_publiees: false,
          statut_public: false,
          date_soumission: new Date().toISOString().slice(0, 16)
        };
        setProjects(prev => [...prev, newProj]);
      }

      // Trigger PRG Redirect loop
      setTimeout(() => {
        appendPrgLog('302 REDIRECT', '/student/dashboard', null, { type: 'success', text: 'Votre projet a été soumis avec succès !' });
        triggerFlash('success', 'Projet enregistré en base de données. PRG Redirect effectué !');
        setCurrentExSelect(null); // Return to list view
        
        // Reset forms
        setSubmitFormTitle('');
        setSubmitFormUrl('');
        setSubmitFormAcces('');
        setSubmitFormExplications('');
        setSubmitFormTheme(null);
        setSubmitFormFile(null);
        setSubmitFormIsGroup(false);
        setSubmitFormGroupName('');
        setSubmitFormGroupMembers([]);

        setTimeout(() => {
          appendPrgLog('GET', '/student/dashboard', null);
        }, 150);
      }, 350);

    } catch (e: any) {
      appendPrgLog('302 REDIRECT', `/student/submit?id=${currentExSelect}`, null, { type: 'error', text: e.message });
      triggerFlash('error', e.message);
    }
  };

  // Add teacher evaluation scores
  const handleProfGradingSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!gradingProject) return;

    appendPrgLog('POST', '/prof/grade', {
      id_projet: gradingProject.id_projet,
      note_design: gradeDesign,
      note_code: gradeCode,
      note_fonc: gradeFonc,
      critique: gradeCritique,
      statut_public: gradePublic
    });

    const updated = projects.map(p => {
      if (p.id_projet === gradingProject.id_projet) {
        return {
          ...p,
          note_design: gradeDesign,
          note_code: gradeCode,
          note_fonc: gradeFonc,
          note_totale: gradeDesign + gradeCode + gradeFonc,
          critique_prof: gradeCritique,
          statut_public: gradePublic
        };
      }
      return p;
    });

    setProjects(updated);

    setTimeout(() => {
      appendPrgLog('302 REDIRECT', '/prof/dashboard', null, { type: 'success', text: 'Évaluation enregistrée avec succès.' });
      triggerFlash('success', 'Note enregistrée en Brouillon. Redirection PRG complétée.');
      setGradingProject(null);

      setTimeout(() => {
        appendPrgLog('GET', '/prof/dashboard', null);
      }, 150);
    }, 300);
  };

  // Publish grades globally key trigger
  const handlePublishGradesAction = (exId: number) => {
    appendPrgLog('POST', '/prof/publish-notes', { id_exercice: exId });

    const updated = projects.map(p => {
      if (p.id_exercice === exId) {
        return { ...p, notes_publiees: true };
      }
      return p;
    });
    setProjects(updated);

    setTimeout(() => {
      appendPrgLog('302 REDIRECT', '/prof/dashboard', null, { type: 'success', text: 'Toutes les copies de cet exercice ont été publiées de manière officielle.' });
      triggerFlash('success', 'Notes publiées et transmises aux bulletins élèves !');
      
      setTimeout(() => {
        appendPrgLog('GET', '/prof/dashboard', null);
      }, 150);
    }, 300);
  };

  // Peer comment submit logic
  const handlePeerCommentSubmit = (e: React.FormEvent, projectId: number) => {
    e.preventDefault();
    const txt = peerCommentText[projectId];
    if (!txt || !loggedUser) return;

    appendPrgLog('POST', '/student/peer-gallery/comment', { id_projet: projectId, comment: txt });

    const newComment: DbComment = {
      id_comment: comments.length + 1,
      id_projet: projectId,
      id_user: loggedUser.id_user,
      pseudonyme: 'Étudiant_' + Math.random().toString(36).substring(2, 6),
      contenu: txt,
      date_publication: new Date().toISOString()
    };

    setComments(prev => [...prev, newComment]);
    setPeerCommentText(prev => ({ ...prev, [projectId]: '' }));

    setTimeout(() => {
      appendPrgLog('302 REDIRECT', '/student/peer-gallery', null, { type: 'success', text: 'Commentaire constructif publié.' });
      triggerFlash('success', 'Peer review ajoutée avec succès !');

      setTimeout(() => {
        appendPrgLog('GET', '/student/peer-gallery', null);
      }, 150);
    }, 250);
  };

  // Admin Publish Exercise action
  const handleAdminExSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!adminExTitle || !adminExDeadline) return;

    appendPrgLog('POST', '/admin/exercise/create', {
      titre: adminExTitle,
      type_exercice: adminExType,
      date_limite: adminExDeadline,
      themes: adminExThemes
    });

    const newExId = exercises.length + 1;
    const newEx: DbExercise = {
      id_exercice: newExId,
      titre: adminExTitle,
      type_exercice: adminExType,
      date_limite: adminExDeadline,
      est_bloque: false
    };

    setExercises(prev => [...prev, newEx]);

    if (adminExThemes) {
      const themesArr = adminExThemes.split(',').map((t, idx) => ({
        id_theme: themes.length + idx + 1,
        id_exercice: newExId,
        nom_theme: t.trim()
      }));
      setThemes(prev => [...prev, ...themesArr]);
    }

    setTimeout(() => {
      appendPrgLog('302 REDIRECT', '/admin/dashboard', null, { type: 'success', text: `Exercice "${adminExTitle}" publié avec succès !` });
      triggerFlash('success', 'Exercice publié, déviations de bases configurées !');
      setAdminExTitle('');
      setAdminExThemes('');

      setTimeout(() => {
        appendPrgLog('GET', '/admin/dashboard', null);
      }, 150);
    }, 300);
  };

  // Admin derogation trigger
  const handleAdminAddExemption = (e: React.FormEvent, exId: number) => {
    e.preventDefault();
    if (!adminExempDate) return;

    appendPrgLog('POST', '/admin/exemption/add', {
      id_exercice: exId,
      id_user: adminExempUser,
      id_groupe: adminExempGroup,
      nouvelle_date: adminExempDate
    });

    const newDero: DbDerogation = {
      id_derogation: derogations.length + 1,
      id_exercice: exId,
      id_user: adminExempUser,
      id_groupe: adminExempGroup,
      nouvelle_date: adminExempDate
    };

    setDerogations(prev => [...prev, newDero]);

    setTimeout(() => {
      appendPrgLog('302 REDIRECT', `/admin/group/manage?id=${exId}`, null, { type: 'success', text: 'Dérogation accordée avec succès.' });
      triggerFlash('success', 'Dérogation horaire enregistrée en BDD !');
      setAdminExempUser(null);
      setAdminExempGroup(null);

      setTimeout(() => {
        appendPrgLog('GET', `/admin/group/manage?id=${exId}`, null);
      }, 150);
    }, 250);
  };

  // Run on startup
  useEffect(() => {
    appendPrgLog('GET', '/auth/login', null);
  }, []);

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col font-sans">
      
      {/* TOP NAVIGATION BAR WITH MULTIPLE INTERACTIVE TABS */}
      <header className="bg-slate-950 border-b border-slate-800 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 bg-blue-600 rounded-xl flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/25 text-lg">
              Ψ
            </div>
            <div>
              <span className="text-md font-bold tracking-tight text-white block">EMSP Assignment Hub</span>
              <span className="text-[10.5px] font-mono text-slate-400">Architecture PHP MVC / PRG Sandbox</span>
            </div>
          </div>

          {/* Tab Selector options */}
          <div className="flex gap-1.5 bg-slate-900 border border-slate-800 p-1.5 rounded-xl text-xs font-semibold">
            <button
              onClick={() => setActiveTab('simulation')}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-lg transition-all ${activeTab === 'simulation' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}
            >
              <Layout className="h-4 w-4" />
              <span>Simulateur PHP Web / PRG</span>
            </button>
            <button
              onClick={() => setActiveTab('database')}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-lg transition-all ${activeTab === 'database' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}
            >
              <DbIcon className="h-4 w-4" />
              <span>Tables phpMyAdmin</span>
            </button>
            <button
              onClick={() => setActiveTab('code')}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-lg transition-all ${activeTab === 'code' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}
            >
              <CodeIcon className="h-4 w-4" />
              <span>Explorateur Code Source</span>
            </button>
          </div>
        </div>
      </header>

      {/* MAIN CONTAINER LAYOUT */}
      <main className="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col">
        
        {/* ==========================================
             TAB 1: INTEGRATED INTERACTIVE PHP SIMULATOR
             ========================================== */}
        {activeTab === 'simulation' && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 flex-1 items-stretch">
            
            {/* Left Side : The Virtual Browser Sandbox screen (Simulates apache host browser) */}
            <div className="lg:col-span-8 flex flex-col border border-slate-800 bg-slate-950 rounded-2xl shadow-xl overflow-hidden min-h-[600px]">
              
              {/* Simulated Browser Frame Bar */}
              <div className="bg-slate-900 px-4 py-3 flex items-center gap-3 border-b border-slate-800 text-xs">
                <div className="flex gap-1.5 items-center">
                  <div className="h-3 w-3 rounded-full bg-rose-500"></div>
                  <div className="h-3 w-3 rounded-full bg-amber-500"></div>
                  <div className="h-3 w-3 rounded-full bg-emerald-500"></div>
                </div>
                {/* Browser URL indicator bar */}
                <div className="flex-1 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800 text-slate-400 flex items-center justify-between font-mono max-w-lg">
                  <span className="truncate">http://localhost/student-hub-php/public/{loggedUser ? `${loggedUser.role === 'etudiant' ? 'student' : loggedUser.role}/dashboard` : 'auth/login'}</span>
                  <Activity className="h-3.5 w-3.5 text-blue-500 animate-pulse shrink-0 ml-2" />
                </div>
              </div>

              {/* Live Browser Content */}
              <div className="bg-slate-50 text-slate-800 flex-1 p-6 relative overflow-y-auto">
                
                {/* Temporary browser flash banners */}
                <AnimatePresence>
                  {flashMessage && (
                    <motion.div 
                      initial={{ opacity: 0, y: -20 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -20 }}
                      className={`mb-6 p-4 rounded-xl border flex items-start gap-2.5 shadow-sm relative ${flashMessage.type === 'success' ? 'bg-emerald-50 text-emerald-950 border-emerald-200' : 'bg-rose-50 text-rose-950 border-rose-200'}`}
                    >
                      {flashMessage.type === 'success' ? <CheckCircle className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" /> : <AlertCircle className="h-4 w-4 text-rose-600 shrink-0 mt-0.5" />}
                      <div>
                        <span className="font-bold text-xs uppercase tracking-wide block">Notification Session PHP :</span>
                        <p className="text-xs font-semibold mt-0.5">{flashMessage.text}</p>
                      </div>
                      <span className="text-[9px] px-1.5 py-0.5 bg-black/5 rounded absolute bottom-2 right-2 font-mono">Flash Once</span>
                    </motion.div>
                  )}
                </AnimatePresence>

                {/* Simulated Roles Header inside "Browser Content" */}
                {loggedUser && (
                  <div className="border-b border-slate-200 pb-3 mb-6 flex justify-between items-center text-xs">
                    <div className="flex items-center gap-2">
                      <div className="h-8 w-8 bg-blue-600 text-white font-extrabold rounded-lg flex items-center justify-center text-sm">
                        {loggedUser.nom.slice(0, 1)}
                      </div>
                      <div>
                        <span className="font-extrabold text-slate-950">{loggedUser.prenom} {loggedUser.nom}</span>
                        <span className="block text-[10px] text-slate-400 capitalize">Utilisateur PHP {loggedUser.role}</span>
                      </div>
                    </div>
                    <button 
                      onClick={handleLogout}
                      className="px-2.5 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 border border-slate-200 transition font-mono rounded text-[10px] font-bold"
                    >
                      session_destroy() ↩
                    </button>
                  </div>
                )}

                {/* CASE A: LOGIN RENDER SCREEN */}
                {!loggedUser && (
                  <div className="max-w-md mx-auto my-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm font-sans space-y-6">
                    <div className="text-center">
                      <h2 className="text-xl font-bold tracking-tight text-slate-900">Se Connecter (Index PHP)</h2>
                      <p className="text-xs text-rose-500 mt-1 uppercase font-mono">Protection CSRF & cryptage password_verify</p>
                    </div>

                    <form onSubmit={(e) => {
                      const selEmail = (e.currentTarget.elements.namedItem('login_user') as HTMLSelectElement).value;
                      handleLoginSubmit(e, selEmail);
                    }} className="space-y-4">
                      
                      {/* CSRF token visual token block */}
                      <div className="bg-slate-50 border border-slate-100 rounded p-2.5 font-mono text-[10.5px] text-slate-500 flex justify-between items-center">
                        <span>POST csrf_token hidden :</span>
                        <span className="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded text-[9.5px]">csrf_298aefb...</span>
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">Sélectionner un Profil EMSP :</label>
                        <select id="login_user" name="login_user" required className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-500 transition">
                          <option value="fatou.traore@emsp.ci">Élève Chef de Groupe : Fatou Traore (EMSP)</option>
                          <option value="jean.koffi@emsp.ci">Élève Membre de Groupe : Jean Koffi</option>
                          <option value="amadou@emsp.ci">Professeur Enseignant : M. Amadou</option>
                          <option value="admin@emsp.ci">Administrateur DSER : Ivob Admin</option>
                        </select>
                      </div>

                      <button type="submit" className="w-full bg-blue-600 hover:bg-blue-700 text-white font-mono text-xs font-bold uppercase py-2.5 rounded-lg tracking-wider transition shadow flex justify-center items-center gap-1.5">
                        <span>POST /auth/login → Redirect</span>
                        <ArrowRight className="h-3.5 w-3.5" />
                      </button>
                    </form>
                  </div>
                )}

                {/* CASE B: STUDENT DASHBOARD */}
                {loggedUser && loggedUser.role === 'etudiant' && (
                  <div className="space-y-6">
                    
                    {/* Mode bar indicators if submissions form open */}
                    {currentExSelect === null ? (
                      <div className="space-y-4 font-sans">
                        <div className="flex justify-between items-center">
                          <h2 className="text-[14px] font-bold uppercase tracking-wider text-slate-400">Devoirs Disponibles (DSER)</h2>
                          <button 
                            onClick={() => {
                              appendPrgLog('GET', '/student/peer-gallery', null);
                              triggerFlash('success', 'Galerie chargée. Projets évalués visibles.');
                              // In sandbox, we transition direct to gallery or simulate clicks
                            }}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white rounded font-mono text-[10px] font-bold px-2.5 py-1.5 flex items-center gap-1 transition"
                          >
                            <Users className="h-3 w-3" />
                            <span>Visiter Peer-Testing Gallery</span>
                          </button>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                          {exercises.map(ex => {
                            const sub = projects.find(p => p.id_exercice === ex.id_exercice && (p.id_user_individuel === loggedUser.id_user || (p.id_groupe && members.some(m => m.id_groupe === p.id_groupe && m.id_user === loggedUser.id_user))));
                            const stat = getSubmissionDeadlineStatus(ex.id_exercice, loggedUser.id_user);
                            const grp = groups.find(g => g.id_exercice === ex.id_exercice && (g.id_chef === loggedUser.id_user || members.some(m => m.id_groupe === g.id_groupe && m.id_user === loggedUser.id_user)));

                            return (
                              <div key={ex.id_exercice} className="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                                <div>
                                  <div className="flex justify-between items-start gap-2">
                                    <span className="p-1 px-1.5 bg-slate-100 text-[10px] font-mono font-bold capitalize text-slate-500 rounded border border-slate-200/50">
                                      {ex.type_exercice}
                                    </span>
                                    {sub ? (
                                      <span className="bg-emerald-100 text-emerald-800 text-[9px] font-bold p-1 rounded font-mono border border-emerald-200">
                                        SUBMITTED
                                      </span>
                                    ) : (
                                      <span className="bg-amber-50 text-amber-600 text-[9px] font-bold p-1 rounded font-mono border border-amber-100">
                                        PENDING
                                      </span>
                                    )}
                                  </div>

                                  <h3 className="font-bold text-sm text-slate-900 mt-2">{ex.titre}</h3>
                                  <p className="text-[10px] text-slate-400 mt-0.5 font-mono">Date Limite : {new Date(ex.date_limite).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}</p>

                                  {grp && (
                                    <div className="bg-indigo-50 border border-indigo-100 rounded p-2 text-[10.5px] mt-2 text-indigo-900 space-y-0.5 font-mono">
                                      <span>👥 Équipe : <strong>{grp.nom_groupe}</strong></span>
                                      <span className="block text-[10px] text-slate-400">Chef : Fatou Traore</span>
                                    </div>
                                  )}

                                  {/* Submitted file stats disclosure */}
                                  {sub && (
                                    <div className="mt-3 border-t border-dashed border-slate-100 pt-2 text-[10.5px] text-slate-500 space-y-1 font-mono bg-slate-50/50 p-2 rounded">
                                      <span className="font-bold block text-[9.5px]">PROJET SOUVERTURE :</span>
                                      <p>• Nom : {sub.titre_projet}</p>
                                      <p>• Thème : {themes.find(t => t.id_theme === sub.id_theme)?.nom_theme || 'Libre'}</p>
                                      <p>• URL : <span className="text-blue-600 underline font-semibold cursor-pointer">{sub.lien_url}</span></p>
                                      {stat.isLate && <p className="text-rose-600 font-bold">⚠️ REMIS AVEC RETARD (PÉNALITÉ)</p>}

                                      {/* Displays notes if teachers published them */}
                                      {sub.notes_publiees && sub.note_totale !== null ? (
                                        <div className="bg-emerald-50 border border-emerald-100 rounded p-2.5 mt-2 space-y-1">
                                          <div className="flex justify-between font-bold text-emerald-950 text-xs">
                                            <span>Note Finale :</span>
                                            <span>{sub.note_totale} / 20.00</span>
                                          </div>
                                          <div className="grid grid-cols-3 text-[9.5px] text-emerald-800 text-center gap-1">
                                            <div>UI: {sub.note_design}/5</div>
                                            <div>Code: {sub.note_code}/5</div>
                                            <div>Fonc: {sub.note_fonc}/10</div>
                                          </div>
                                          {sub.critique_prof && (
                                            <p className="text-[10px] text-slate-600 bg-white p-1 rounded font-medium italic border border-emerald-100">
                                              Feedback encadrant : "{sub.critique_prof}"
                                            </p>
                                          )}
                                        </div>
                                      ) : sub.note_totale !== null ? (
                                        <div className="bg-slate-100 border border-slate-200 rounded p-2 text-center text-[9.5px] text-slate-400 mt-2">
                                          Projet corrigé en brouillon. Note à reveler bientôt.
                                        </div>
                                      ) : null}
                                    </div>
                                  )}
                                </div>

                                <button
                                  onClick={() => {
                                    appendPrgLog('GET', `/student/submit?id=${ex.id_exercice}`, null);
                                    setCurrentExSelect(ex.id_exercice);
                                    setSubmitFormIsGroup(ex.type_exercice === 'groupe');
                                    
                                    // populate values if existing
                                    if (sub) {
                                      setSubmitFormTitle(sub.titre_projet);
                                      setSubmitFormUrl(sub.lien_url);
                                      setSubmitFormAcces(sub.acces_test);
                                      setSubmitFormExplications(sub.explications);
                                      setSubmitFormTheme(sub.id_theme);
                                    }
                                  }}
                                  className="w-full bg-slate-900 border border-slate-200 hover:bg-slate-800 text-white font-mono text-[10.5px] py-1.5 rounded mt-4 transition"
                                >
                                  {sub ? 'modifier mon projet (GET /student/submit)' : 'déposer mon projet (GET /student/submit)'}
                                </button>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    ) : (
                      
                      // SUBMIT FORM WINDOW FOR STUDENTS
                      <div className="bg-white rounded-xl border border-slate-200 p-5 space-y-4 text-xs font-sans">
                        <div className="flex justify-between items-center bg-slate-50 p-2 rounded">
                          <button 
                            onClick={() => setCurrentExSelect(null)}
                            className="font-bold text-slate-500 hover:text-blue-500"
                          >
                            ← retour au dashboard
                          </button>
                          <span className="font-bold font-mono">DÉPÔT PROJET : {exercises.find(e => e.id_exercice === currentExSelect)?.titre}</span>
                        </div>

                        <form onSubmit={handleProjectSubmitAction} className="space-y-4">
                          
                          {/* Chef de groupe interface display inside Simulator if required */}
                          {submitFormIsGroup && (
                            <div className="bg-indigo-50 border border-indigo-100 rounded-xl p-4 space-y-3">
                              <span className="font-bold text-indigo-900">👥 SECTION CHEF DE GROUPE DES ACTIONS</span>
                              <p className="text-[11px] text-slate-500 leading-relaxed">Puisque l'exercice est de type groupe, cochez un coéquipier non affecté et donnez un nom à l'équipe.</p>
                              
                              <div>
                                <label className="block text-[11px] text-indigo-900 font-semibold">Nom de l'équipe :</label>
                                <input 
                                  value={submitFormGroupName} 
                                  onChange={e => setSubmitFormGroupName(e.target.value)} 
                                  type="text" 
                                  required 
                                  placeholder="Ex: Binôme EMSP-DSER 4"
                                  className="w-full bg-white border border-indigo-200 p-1 px-2.5 rounded text-xs mt-1"
                                />
                              </div>

                              <div>
                                <label className="block text-[11px] text-indigo-900 font-semibold mb-1">Cochez vos coéquipiers libres :</label>
                                <div className="space-y-1 bg-white p-2 border border-indigo-100 rounded max-h-24 overflow-y-auto">
                                  {users.filter(u => u.role === 'etudiant' && u.id_user !== loggedUser.id_user).map(st => (
                                    <label key={st.id_user} className="flex items-center gap-2 cursor-pointer text-slate-600 text-[10.5px]">
                                      <input 
                                        type="checkbox" 
                                        checked={submitFormGroupMembers.includes(st.id_user)}
                                        onChange={(event) => {
                                          if (event.target.checked) setSubmitFormGroupMembers(prev => [...prev, st.id_user]);
                                          else setSubmitFormGroupMembers(prev => prev.filter(mid => mid !== st.id_user));
                                        }}
                                        className="rounded border-slate-300 text-indigo-600"
                                      />
                                      <span>{st.nom} {st.prenom}</span>
                                    </label>
                                  ))}
                                </div>
                              </div>
                            </div>
                          )}

                          <div>
                            <label className="block font-semibold">Titre du Projet :</label>
                            <input 
                              value={submitFormTitle} 
                              onChange={e => setSubmitFormTitle(e.target.value)} 
                              type="text" 
                              required 
                              placeholder="e.g. EMSP Mini Stock"
                              className="w-full bg-white border border-slate-300 p-1.5 px-2.5 rounded text-xs mt-1 focus:border-blue-500 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block font-semibold">Thème :</label>
                            <select 
                              value={submitFormTheme || ''} 
                              onChange={e => setSubmitFormTheme(Number(e.target.value) || null)} 
                              className="w-full bg-white border border-slate-300 p-1.5 px-2.5 rounded text-xs mt-1"
                            >
                              <option value="">-- Aucun Thème --</option>
                              {themes.filter(t => t.id_exercice === currentExSelect).map(t => (
                                <option key={t.id_theme} value={t.id_theme}>{t.nom_theme}</option>
                              ))}
                            </select>
                          </div>

                          <div>
                            <label className="block font-semibold">URL de déploiement (Cloud Run) :</label>
                            <input 
                              value={submitFormUrl} 
                              onChange={e => setSubmitFormUrl(e.target.value)} 
                              type="url" 
                              required 
                              placeholder="https://simulation.emsp-app.run.app"
                              className="w-full bg-white border border-slate-300 p-1.5 px-2.5 rounded text-xs mt-1 focus:border-blue-500 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block font-semibold">Codes de test professeurs :</label>
                            <input 
                              value={submitFormAcces} 
                              onChange={e => setSubmitFormAcces(e.target.value)} 
                              placeholder="admin / adminpassword123, teacher / secretpwd"
                              className="w-full bg-white border border-slate-300 p-1.5 px-2.5 rounded text-xs mt-1 focus:border-blue-500 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block font-semibold">Explications (Comment / Pourquoi) :</label>
                            <textarea 
                              value={submitFormExplications} 
                              onChange={e => setSubmitFormExplications(e.target.value)} 
                              rows={2} 
                              placeholder="Détaillez le fonctionnement technique général..." 
                              className="w-full bg-white border border-slate-300 p-1.5 px-2.5 rounded text-xs mt-1"
                            ></textarea>
                          </div>

                          <div className="bg-slate-50 p-3 rounded border border-slate-200">
                            <span className="block font-bold mb-1">📁 Upload du Cahier des Charges (.pdf) :</span>
                            <span className="text-[10px] text-slate-400 block mb-2">Simule mime_content_type() et move_uploaded_file()</span>
                            
                            <div className="flex gap-2">
                              <button 
                                type="button" 
                                onClick={() => {
                                  setSubmitFormFile('uploads/cdc_' + currentExSelect + '_' + loggedUser.nom + '_gen4b.pdf');
                                  triggerFlash('success', 'Document PDF téléchargé virtuellement.');
                                }}
                                className="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1.5 rounded font-mono font-bold text-[10px]"
                              >
                                {submitFormFile ? '✓ Document chargé' : 'Simuler upload PDF'}
                              </button>
                              {submitFormFile && <span className="text-[10px] text-emerald-600 font-mono mt-1 mt-1.5 select-none">{submitFormFile}</span>}
                            </div>
                          </div>

                          <button 
                            type="submit" 
                            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-mono text-xs font-bold py-2.5 px-3 rounded uppercase tracking-wider"
                          >
                            POST /student/submit (PRG Redirect)
                          </button>
                        </form>
                      </div>
                    )}
                  </div>
                )}

                {/* CASE C: PROFESSOR GRADE DASHBOARD */}
                {loggedUser && loggedUser.role === 'prof' && (
                  <div className="space-y-6">
                    
                    {!gradingProject ? (
                      <div className="space-y-4">
                        <h2 className="text-sm font-bold text-slate-400 uppercase tracking-wider">Devoirs à Corriger de l'EMSP</h2>
                        
                        <div className="space-y-4">
                          {exercises.map(ex => {
                            const subs = projects.filter(p => p.id_exercice === ex.id_exercice);
                            const noted = subs.filter(sub => sub.note_totale !== null).length;
                            const isPub = subs.some(sub => sub.notes_publiees);

                            return (
                              <div key={ex.id_exercice} className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3 text-xs font-sans">
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                  <div>
                                    <h3 className="font-bold text-sm text-slate-900">{ex.titre}</h3>
                                    <p className="text-[10.5px] text-slate-400 mt-0.5">Format: {ex.type_exercice} • Date limite: {new Date(ex.date_limite).toLocaleDateString('fr-FR')}</p>
                                  </div>
                                  <div className="flex items-center gap-2">
                                    <span className="font-mono bg-slate-50 border p-1 rounded font-bold text-slate-600">{noted} / {subs.length} Notés</span>
                                    
                                    {subs.length > 0 && (
                                      isPub ? (
                                        <span className="p-1 px-1.5 bg-emerald-100 text-emerald-800 font-mono font-bold rounded">NOTES PUBLIÉES</span>
                                      ) : (
                                        <button 
                                          onClick={() => handlePublishGradesAction(ex.id_exercice)}
                                          className="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 font-mono font-bold rounded shadow-sm text-[10.5px]"
                                        >
                                          POST Publier
                                        </button>
                                      )
                                    )}
                                  </div>
                                </div>

                                <div className="space-y-2">
                                  {subs.length === 0 ? (
                                    <p className="italic text-slate-400 py-2">Aucun binôme n'a encore soumis de projet.</p>
                                  ) : (
                                    <div className="space-y-1.5">
                                      {subs.map(p => {
                                        const grp = p.id_groupe ? groups.find(g => g.id_groupe === p.id_groupe) : null;
                                        const indUser = p.id_user_individuel ? users.find(u => u.id_user === p.id_user_individuel) : null;
                                        const creator = grp ? `Binôme : ${grp.nom_groupe}` : indUser ? `Solo : ${indUser.prenom} ${indUser.nom}` : 'Solo : (inconnu)';

                                        return (
                                          <div key={p.id_projet} className="bg-slate-50 border rounded p-2.5 flex justify-between items-center hover:bg-slate-100/50 transition">
                                            <div>
                                              <span className="font-bold text-slate-900 block">{p.titre_projet}</span>
                                              <span className="block text-[10px] text-slate-400 mt-0.5">{creator} • <a href={p.lien_url} className="text-blue-500 underline" target="_blank" rel="noopener noreferrer">Visiter le site</a></span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                              <span className="font-mono text-[10.5px] font-bold">{p.note_totale !== null ? `${p.note_totale}/20` : 'À noter'}</span>
                                              <button 
                                                onClick={() => {
                                                  setGradingProject(p);
                                                  setGradeDesign(p.note_design || 4.00);
                                                  setGradeCode(p.note_code || 4.00);
                                                  setGradeFonc(p.note_fonc || 8.00);
                                                  setGradeCritique(p.critique_prof || '');
                                                  setGradePublic(p.statut_public || false);
                                                }}
                                                className="bg-slate-900 hover:bg-slate-800 text-white p-1 px-2.5 rounded font-mono font-bold text-[10px]"
                                              >
                                                Évaluer
                                              </button>
                                            </div>
                                          </div>
                                        );
                                      })}
                                    </div>
                                  )}
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    ) : (
                      
                      // INDIVIDUAL GRADING FORM PANEL FOR PROFESSORS
                      <div className="bg-white rounded-xl border border-slate-200 p-5 space-y-4 text-xs font-sans">
                        <div className="flex justify-between items-center bg-slate-50 p-2 rounded">
                          <button onClick={() => setGradingProject(null)} className="font-bold text-slate-500">← retour aux corrections</button>
                          <span className="font-bold font-mono">Évaluation : {gradingProject.titre_projet}</span>
                        </div>

                        <form onSubmit={handleProfGradingSubmit} className="space-y-4">
                          <div className="bg-indigo-50 border border-indigo-100 rounded p-2.5 font-mono text-[10px]">
                            • URL : <a href={gradingProject.lien_url} target="_blank" className="font-bold underline text-indigo-600">{gradingProject.lien_url}</a><br/>
                            • Credentials de test : <strong>{gradingProject.acces_test}</strong>
                          </div>

                          <div className="grid grid-cols-3 gap-2 font-mono">
                            <div>
                              <label className="block text-[10.5px] font-sans font-bold">Design (/5) :</label>
                              <input 
                                value={gradeDesign} 
                                onChange={e => setGradeDesign(Number(e.target.value) || 0)} 
                                type="number" 
                                min={0} max={5} step={0.25} 
                                className="w-full bg-white border border-slate-300 rounded p-1 text-center mt-1 text-xs" 
                              />
                            </div>
                            <div>
                              <label className="block text-[10.5px] font-sans font-bold">Code (/5) :</label>
                              <input 
                                value={gradeCode} 
                                onChange={e => setGradeCode(Number(e.target.value) || 0)} 
                                type="number" 
                                min={0} max={5} step={0.25} 
                                className="w-full bg-white border border-slate-300 rounded p-1 text-center mt-1 text-xs" 
                              />
                            </div>
                            <div>
                              <label className="block text-[10.5px] font-sans font-bold">Fonc (/10) :</label>
                              <input 
                                value={gradeFonc} 
                                onChange={e => setGradeFonc(Number(e.target.value) || 0)} 
                                type="number" 
                                min={0} max={10} step={0.25} 
                                className="w-full bg-white border border-slate-300 rounded p-1 text-center mt-1 text-xs" 
                              />
                            </div>
                          </div>

                          <div className="border-t border-dashed py-2 text-right font-mono text-sm">
                            Total Calculé : <strong className="text-blue-600">{(gradeDesign + gradeCode + gradeFonc).toFixed(2)}</strong> / 20.00
                          </div>

                          <div>
                            <label className="block font-bold">Critique & Feedbacks Constructif :</label>
                            <textarea 
                              value={gradeCritique} 
                              onChange={e => setGradeCritique(e.target.value)} 
                              rows={3} 
                              placeholder="Génial, MVC parfaitement implémenté..."
                              className="w-full bg-white border border-slate-300 rounded p-1.5 text-xs font-sans mt-1"
                            ></textarea>
                          </div>

                          <label className="flex items-center gap-2 bg-slate-50 p-2 border border-slate-200 rounded cursor-pointer select-none">
                            <input 
                              type="checkbox" 
                              checked={gradePublic} 
                              onChange={e => setGradePublic(e.target.checked)}
                              className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                              <span className="block text-[11px] font-semibold text-slate-800">Publier dans la galerie Peer-Testing</span>
                              <span className="block text-[9.5px] text-slate-400 mt-0.5">Permets aux camarades de promotion d'analyser le prototype.</span>
                            </div>
                          </label>

                          <button 
                            type="submit" 
                            className="w-full bg-slate-900 border border-slate-200 hover:bg-slate-800 text-white font-mono text-xs font-bold py-2 px-3 rounded uppercase tracking-wider"
                          >
                            POST /prof/grade (PRG Redirect)
                          </button>
                        </form>
                      </div>
                    )}
                  </div>
                )}

                {/* CASE D: ADMINISTRATOR CONTROL PANEL */}
                {loggedUser && loggedUser.role === 'admin' && (
                  <div className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                      
                      {/* Column 1: Publish assignments */}
                      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs font-sans space-y-4">
                        <span className="block font-bold font-mono text-slate-900 border-b pb-1.5 uppercase">🛡️ Publier un nouvel exercice</span>
                        
                        <form onSubmit={handleAdminExSubmit} className="space-y-3">
                          <div>
                            <label className="block font-semibold">Titre du Devoir :</label>
                            <input 
                              value={adminExTitle}
                              onChange={e => setAdminExTitle(e.target.value)}
                              type="text" 
                              required 
                              placeholder="Mini-Projet MVC PHP"
                              className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1" 
                            />
                          </div>

                          <div className="grid grid-cols-2 gap-2">
                            <div>
                              <label className="block font-semibold">Participation :</label>
                              <select 
                                value={adminExType} 
                                onChange={e => setAdminExType(e.target.value as any)} 
                                className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1"
                              >
                                <option value="individuel">Individuelle</option>
                                <option value="groupe">Groupe</option>
                              </select>
                            </div>
                            <div>
                              <label className="block font-semibold">Date Limite :</label>
                              <input 
                                value={adminExDeadline} 
                                onChange={e => setAdminExDeadline(e.target.value)} 
                                type="datetime-local" 
                                required 
                                className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1 font-mono" 
                              />
                            </div>
                          </div>

                          <div>
                            <label className="block font-semibold">Thèmes (Topics) - séparez par des virgules :</label>
                            <input 
                              value={adminExThemes} 
                              onChange={e => setAdminExThemes(e.target.value)} 
                              placeholder="E-Commerce, Stock DSER, Vote Club"
                              className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1" 
                            />
                          </div>

                          <button type="submit" className="w-full bg-blue-600 hover:bg-blue-700 text-white font-mono text-xs font-bold uppercase py-2.5 rounded shadow">
                            POST /admin/exercise/create (PRG)
                          </button>
                        </form>
                      </div>

                      {/* Column 2: Manage exemptions (Derogations) */}
                      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs font-sans space-y-4">
                        <span className="block font-bold font-mono text-slate-900 border-b pb-1.5 uppercase">🕒 Accorder une Dérogation</span>
                        
                        <form onSubmit={(e) => handleAdminAddExemption(e, adminExempExercice)} className="space-y-3">
                          <div>
                            <label className="block font-semibold">Sélectionner l'exercice ciblé :</label>
                            <select
                                value={adminExempExercice}
                                onChange={e => setAdminExempExercice(Number(e.target.value))}
                                className="w-full bg-white border rounded p-1.5 mt-1"
                              >
                              {exercises.map(ex => (
                                <option key={ex.id_exercice} value={ex.id_exercice}>{ex.titre}</option>
                              ))}
                            </select>
                          </div>

                          <div className="grid grid-cols-2 gap-2">
                            <div>
                              <label className="block font-semibold">Élève Individuel :</label>
                              <select 
                                value={adminExempUser || ''} 
                                onChange={e => setAdminExempUser(Number(e.target.value) || null)} 
                                className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1"
                              >
                                <option value="">-- Aucun --</option>
                                {users.filter(u => u.role === 'etudiant').map(st => (
                                  <option key={st.id_user} value={st.id_user}>{st.nom} {st.prenom}</option>
                                ))}
                              </select>
                            </div>
                            <div>
                              <label className="block font-semibold">OU Groupe ciblé :</label>
                              <select 
                                value={adminExempGroup || ''} 
                                onChange={e => setAdminExempGroup(Number(e.target.value) || null)} 
                                className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1"
                              >
                                <option value="">-- Aucun --</option>
                                {groups.map(gp => (
                                  <option key={gp.id_groupe} value={gp.id_groupe}>{gp.nom_groupe}</option>
                                ))}
                              </select>
                            </div>
                          </div>

                          <div>
                            <label className="block font-semibold">Nouvelle Échéance Élaborée :</label>
                            <input 
                              value={adminExempDate} 
                              onChange={e => setAdminExempDate(e.target.value)} 
                              type="datetime-local" 
                              required 
                              className="w-full bg-white border border-slate-300 rounded p-1.5 mt-1 font-mono" 
                            />
                          </div>

                          <button type="submit" className="w-full bg-slate-900 border border-slate-200 hover:bg-slate-800 text-white font-mono text-xs font-bold uppercase py-2.5 rounded shadow">
                            POST /admin/exemption/add (PRG)
                          </button>
                        </form>
                      </div>

                    </div>
                  </div>
                )}

              </div>
            </div>

            {/* Right Side : The HTTP Live PRG Console Moniteur (Indispensable for learning!) */}
            <div className="lg:col-span-4 border border-slate-800 bg-slate-950 rounded-2xl p-5 flex flex-col justify-between shadow-xl min-h-[400px]">
              <div className="space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <div className="flex items-center gap-2">
                    <TerminalIcon className="text-blue-500 h-5 w-5" />
                    <span className="font-bold text-xs uppercase tracking-wider font-mono">Moniteur Requêtes PRG / HTTP</span>
                  </div>
                  <span className="text-[10px] bg-slate-900 text-slate-400 font-mono px-2 py-0.5 rounded border border-slate-800">
                    Live Debugger
                  </span>
                </div>
                <p className="text-[11px] text-slate-400 leading-relaxed">
                  Ce moniteur capture l'activité réseau simulée de votre serveur local. Remarquez comment chaque requête state-changing <span className="text-amber-500 font-semibold font-mono">POST</span> redirige immédiatement vers une URL neutre en <span className="text-emerald-500 font-semibold font-mono">GET</span> !
                </p>

                {/* Scrollable logs list */}
                <div className="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                  {prgLogs.map((log, index) => (
                    <motion.div 
                      key={index}
                      initial={{ opacity: 0, x: 20 }}
                      animate={{ opacity: 1, x: 0 }}
                      className="border border-slate-800/80 bg-slate-900/60 p-3 rounded-xl space-y-2 text-xs font-mono shadow-sm"
                    >
                      <div className="flex justify-between items-center pb-1 border-b border-slate-800/50">
                        <span className={`px-2 py-0.5 rounded text-[9px] font-bold ${log.method === 'POST' ? 'bg-amber-500/20 text-amber-500' : log.method === '302 REDIRECT' ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400'}`}>
                          {log.method}
                        </span>
                        <span className="text-slate-500 text-[10px]">{log.timestamp}</span>
                      </div>

                      <div className="space-y-1">
                        <p className="text-slate-300 font-semibold text-[11px]">{log.uri}</p>
                        <p className="text-slate-500 text-[10px]">Status Code: <span className="text-slate-400 font-semibold">{log.statusCode} {log.method === 'GET' ? 'OK' : 'Found'}</span></p>
                      </div>

                      {log.payload && (
                        <div className="bg-black/25 text-[10px] text-slate-400 p-2 rounded overflow-x-auto max-h-20">
                          <strong>Payload (Variables class):</strong>
                          <pre className="mt-1 text-[9px]">{JSON.stringify(log.payload, null, 2)}</pre>
                        </div>
                      )}

                      {log.flashMsg && (
                        <div className="bg-emerald-500/10 border border-emerald-500/20 text-[10px] text-emerald-300 p-1.5 rounded">
                          <strong>$_SESSION['flash']:</strong> "{log.flashMsg.text}"
                        </div>
                      )}
                    </motion.div>
                  ))}
                </div>
              </div>

              {/* Quick explanations of PHP architecture definitions */}
              <div className="border-t border-slate-800 pt-4 mt-4 text-[10.5px] text-slate-400 space-y-2">
                <span className="font-bold text-[10px] text-white block uppercase tracking-wider">Pourquoi l'architecture PRG ?</span>
                <p className="leading-relaxed">
                  Sans PRG, si votre prof recharge la page GET après avoir noté un projet, le navigateur renvoie le formulaire d'évaluation et crée un doublon de note en BDD ! Le redirect forcé élimine ce risque à 100%.
                </p>
              </div>

            </div>

          </div>
        )}

        {/* ==========================================
             TAB 2: INTEGRATED PHPMYADMIN INSPECTOR SCREEN
             ========================================== */}
        {activeTab === 'database' && (
          <div className="space-y-6 flex-1">
            <div className="bg-slate-950 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
              <div className="flex justify-between items-center border-b border-slate-800 pb-4">
                <div className="flex items-center gap-3">
                  <div className="h-9 w-9 bg-emerald-600 rounded-lg flex items-center justify-center text-white text-lg font-bold">
                    P
                  </div>
                  <div>
                    <h2 className="text-md font-bold tracking-tight text-white font-mono">phpMyAdmin Simulation Studio</h2>
                    <p className="text-xs text-slate-400 mt-1">Gérez, inspectez, et requetez les tables PDO MySQL réactives : <span className="text-emerald-500 font-mono">emsp_assignment_db</span></p>
                  </div>
                </div>

                <button 
                  onClick={() => {
                    navigator.clipboard.writeText(phpCodeFiles[0].content);
                    triggerFlash('success', 'Code SQL d\'intégration copié dans le presse-papiers.');
                  }}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white font-mono text-xs font-bold px-3 py-2 rounded flex items-center gap-1.5 transition"
                >
                  <Copy className="h-3.5 w-3.5" />
                  <span>Copier le database.sql setup</span>
                </button>
              </div>

              {/* Grid mapping simulated tables live! */}
              <div className="space-y-6">
                
                {/* 1. Utilisateurs Table */}
                <div className="border border-slate-800 bg-slate-900/40 rounded-xl overflow-hidden shadow-sm">
                  <div className="bg-slate-850 px-4 py-2.5 border-b border-slate-800 flex justify-between items-center text-xs">
                    <span className="font-bold font-mono text-slate-300">📁 Table : utilisateurs (Users catalog)</span>
                    <span className="text-[10px] font-mono text-slate-500">Record count: {users.length}</span>
                  </div>

                  <div className="overflow-x-auto text-[10.5px]">
                    <table className="w-full text-left font-mono border-collapse">
                      <thead className="bg-slate-950 text-slate-400 border-b border-slate-800">
                        <tr>
                          <th className="px-4 py-2">id_user (PK)</th>
                          <th className="px-4 py-2">nom</th>
                          <th className="px-4 py-2">prenom</th>
                          <th className="px-4 py-2">email</th>
                          <th className="px-4 py-2">role</th>
                          <th className="px-4 py-2">password_hash</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-800/50">
                        {users.map(u => (
                          <tr key={u.id_user} className="hover:bg-slate-850/50">
                            <td className="px-4 py-1.5 font-bold text-amber-500">{u.id_user}</td>
                            <td className="px-4 py-1.5">{u.nom}</td>
                            <td className="px-4 py-1.5">{u.prenom}</td>
                            <td className="px-4 py-1.5 text-blue-400">{u.email}</td>
                            <td className="px-4 py-1.5 capitalize font-bold text-slate-300">{u.role}</td>
                            <td className="px-4 py-1.5 text-slate-600 truncate max-w-[120px]">$2y$10$tM/G.x00K9p3b/YV6xWvOepbSgfeK...</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* 2. Projets Submissions Table */}
                <div className="border border-slate-800 bg-slate-900/40 rounded-xl overflow-hidden shadow-sm">
                  <div className="bg-slate-850 px-4 py-2.5 border-b border-slate-800 flex justify-between items-center text-xs">
                    <span className="font-bold font-mono text-slate-300">📁 Table : projets (Submissions & Grades)</span>
                    <span className="text-[10px] font-mono text-slate-500">Record count: {projects.length}</span>
                  </div>

                  <div className="overflow-x-auto text-[10.5px]">
                    <table className="w-full text-left font-mono border-collapse">
                      <thead className="bg-slate-950 text-slate-400 border-b border-slate-800">
                        <tr>
                          <th className="px-3 py-2">id_projet (PK)</th>
                          <th className="px-3 py-2">title</th>
                          <th className="px-3 py-2">id_exe (FK)</th>
                          <th className="px-3 py-2">id_theme (FK)</th>
                          <th className="px-3 py-2">id_user_solo</th>
                          <th className="px-3 py-2">id_groupe</th>
                          <th className="px-3 py-2">URL</th>
                          <th className="px-3 py-2 text-center">Score /20</th>
                          <th className="px-3 py-2 text-center">Publiée</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-800/50">
                        {projects.map(p => (
                          <tr key={p.id_projet} className="hover:bg-slate-850/50">
                            <td className="px-3 py-1.5 font-bold text-amber-500">{p.id_projet}</td>
                            <td className="px-3 py-1.5 font-bold text-white">{p.titre_projet}</td>
                            <td className="px-3 py-1.5 text-blue-500">{p.id_exercice}</td>
                            <td className="px-3 py-1.5 text-blue-500">{p.id_theme || 'NULL'}</td>
                            <td className="px-3 py-1.5">{p.id_user_individuel || 'NULL'}</td>
                            <td className="px-3 py-1.5">{p.id_groupe || 'NULL'}</td>
                            <td className="px-3 py-1.5 text-slate-500 underline truncate max-w-[120px]">{p.lien_url}</td>
                            <td className="px-3 py-1.5 text-center font-bold text-emerald-400">
                              {p.note_totale !== null ? p.note_totale.toFixed(1) : 'NULL'}
                            </td>
                            <td className="px-3 py-1.5 text-center font-bold">
                              {p.notes_publiees ? <span className="text-emerald-500">1 (TRUE)</span> : <span className="text-rose-500">0 (FALSE)</span>}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* 3. Groupes Table */}
                <div className="border border-slate-800 bg-slate-900/40 rounded-xl overflow-hidden shadow-sm">
                  <div className="bg-slate-850 px-4 py-2.5 border-b border-slate-800 flex justify-between items-center text-xs">
                    <span className="font-bold font-mono text-slate-300">📁 Table : groupes (Student Teams)</span>
                    <span className="text-[10px] font-mono text-slate-500">Record count: {groups.length}</span>
                  </div>

                  <div className="overflow-x-auto text-[10.5px]">
                    <table className="w-full text-left font-mono border-collapse">
                      <thead className="bg-slate-950 text-slate-400 border-b border-slate-800">
                        <tr>
                          <th className="px-4 py-2">id_groupe (PK)</th>
                          <th className="px-4 py-2">id_exercice (FK)</th>
                          <th className="px-4 py-2">id_chef (FK)</th>
                          <th className="px-4 py-2">nom_groupe</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-800/50">
                        {groups.map(g => (
                          <tr key={g.id_groupe} className="hover:bg-slate-850/50">
                            <td className="px-4 py-1.5 font-bold text-amber-500">{g.id_groupe}</td>
                            <td className="px-4 py-1.5">{g.id_exercice}</td>
                            <td className="px-4 py-1.5">{g.id_chef}</td>
                            <td className="px-4 py-1.5 text-white font-bold">{g.nom_groupe}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>
          </div>
        )}

        {/* ==========================================
             TAB 3: BEAUTIFUL PHP EMBEDDED SOURCE EXPLORER
             ========================================== */}
        {activeTab === 'code' && (
          <div className="grid grid-cols-1 md:grid-cols-12 gap-8 flex-1 items-stretch">
            
            {/* Sidebar directory files browser */}
            <div className="md:col-span-4 border border-slate-800 bg-slate-950 rounded-2xl p-4 flex flex-col justify-between shadow-xl min-h-[500px]">
              <div className="space-y-4">
                <span className="block font-bold text-xs uppercase tracking-wider font-mono border-b border-slate-800 pb-2">📂 Arborescence du Projet PHP</span>
                
                {/* File search query bar */}
                <input 
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                  placeholder="Chercher un fichier (e.g. Controller.php)..."
                  className="w-full bg-slate-900 border border-slate-800 text-xs px-3 py-2 rounded-lg text-white"
                />

                {/* Vertical tree selections */}
                <div className="space-y-1 overflow-y-auto max-h-[350px] pr-1 scrollbar-thin">
                  {filteredFiles.map((file, idx) => (
                    <button
                      key={idx}
                      onClick={() => setSelectedFile(file)}
                      className={`w-full flex items-center justify-between p-2 rounded-lg transition text-left text-xs ${selectedFile.path === file.path ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent'}`}
                    >
                      <div className="flex items-center gap-2">
                        {file.path.includes('.php') ? <CodeIcon className="h-3.5 w-3.5" /> : <DbIcon className="h-3.5 w-3.5 text-slate-500" />}
                        <span className="font-mono truncate">{file.path}</span>
                      </div>
                      <ArrowRight className="h-3 w-3 shrink-0" />
                    </button>
                  ))}
                </div>
              </div>

              {/* Downloads/ZIP notice export footer */}
              <div className="bg-slate-900/60 p-4 border border-slate-800/80 rounded-xl space-y-2 text-[11px] text-slate-400 leading-relaxed font-mono">
                <span className="font-bold text-[10px] text-white uppercase block">Exporter le projet :</span>
                <span>Pour copier ces codes locaux et les exécuter sur votre XAMPP, utilisez le bouton d'exportation de fichier ZIP de la plateforme dans le menu Paramètres ou copiez-les individuellement ci-contre.</span>
              </div>
            </div>

            {/* Right side : Syntax Highlighting view code */}
            <div className="md:col-span-8 flex flex-col border border-slate-800 bg-slate-950 rounded-2xl shadow-xl overflow-hidden min-h-[500px]">
              
              {/* Active file explorer header */}
              <div className="bg-slate-900 px-4 py-3 flex items-center justify-between border-b border-slate-800 text-xs text-slate-300 font-mono">
                <div className="flex items-center gap-2">
                  <span className="p-1 bg-slate-950 text-emerald-400 font-bold rounded text-[9.5px]">MVC</span>
                  <span>{selectedFile.path}</span>
                </div>

                <div className="flex gap-2">
                  <button 
                    onClick={() => handleCopyCode(selectedFile)}
                    className="bg-slate-850 hover:bg-slate-800 border border-slate-700 hover:border-slate-600 text-[10.5px] px-3 py-1.5 rounded inline-flex items-center gap-1.5 font-bold transition text-white"
                  >
                    {copiedFile === selectedFile.path ? <Check className="h-3.5 w-3.5 text-emerald-500" /> : <Copy className="h-3.5 w-3.5" />}
                    <span>{copiedFile === selectedFile.path ? 'Copié !' : 'Copier le code'}</span>
                  </button>
                </div>
              </div>

              {/* Syntax code render area */}
              <div className="flex-1 p-6 bg-slate-950 text-slate-300 font-mono text-xs overflow-auto max-h-[500px]">
                <pre className="leading-relaxed whitespace-pre font-mono">{selectedFile.content}</pre>
              </div>

            </div>

          </div>
        )}

      </main>
    </div>
  );
}
