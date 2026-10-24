import { iniciarMascaras } from './mascaras';
import { iniciarInterface } from './interface';
import { iniciarEnderecos } from './endereco';
import { iniciarAvaliacoes } from './avaliacoes';
import { iniciarAgendamento } from './agendamento';
import { iniciarChat } from './chat';
import { iniciarValidacao } from './validacao';
import { iniciarCombobox } from './combobox';

// Cada função procura seus elementos na página e registra os eventos necessários.
iniciarMascaras();
iniciarInterface();
iniciarEnderecos();
iniciarAvaliacoes();
iniciarAgendamento();
iniciarChat();
iniciarValidacao();
iniciarCombobox();
