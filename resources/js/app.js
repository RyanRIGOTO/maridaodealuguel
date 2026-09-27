import { iniciarMascaras } from './mascaras';
import { iniciarInterface } from './interface';
import { iniciarEnderecos } from './endereco';
import { iniciarAvaliacoes } from './avaliacoes';
import { iniciarAgendamento } from './agendamento';

// Cada função procura seus elementos na página e registra os eventos necessários.
iniciarMascaras();
iniciarInterface();
iniciarEnderecos();
iniciarAvaliacoes();
iniciarAgendamento();
