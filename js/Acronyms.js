// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

var Acronym = Class.create({
  initialize: function(id, context, acronym, definition, comment, url) {
    this.set(id, context, acronym, definition, comment, url);
  },
  clone: function() {
    return new Acronym(
        this.id,
        this.context,
        this.acronym,
        this.definition,
        this.comment,
        this.url);
  },
  set: function(id, context, acronym, definition, comment, url) {
    this.id = id;
    this.context = context;
    this.acronym = acronym;
    this.definition = definition;
    this.comment = comment;
    this.url = url;
  },
  setFrom: function(other) {
    this.set(
        other.id,
        other.context,
        other.acronym,
        other.definition,
        other.comment,
        other.url);
  },
  isEqual: function(other) {
    return this.id === other.id
        && this.context === other.context
        && this.acronym === other.acronym
        && this.definition === other.definition
        && this.comment === other.comment
        && this.url === other.url;
  },
  setOption: function(option) {
    var changed = this.isChanged();
    var deleted = this.isDeleted();
    var mark = deleted ? '- ' : (changed ? '* ' : '');
    option.text = mark + this.acronym + " (" + this.context + "): " + this.definition;
    set_class(option, 'red', changed);
  },
  isChanged: function() {
    return changes.isChanged(this);
  },
  isDeleted: function() {
    return typeof this.id === 'string'
        && this.id.indexOf('delete') === 0;
  },
  isNew: function() {
    return typeof this.id === 'string'
        && this.id.indexOf('new') === 0;
  },
  markDeleted: function() {
    this.id = 'delete-' + this.id;
  },
  unmarkDeleted: function() {
    this.id = this.id.substr(7);
  }

});

Acronym.fromParameters = function(parameters) {
  return new Acronym(
      parameters.id,
      parameters.context,
      parameters.acronym,
      parameters.definition,
      parameters.comment,
      parameters.url);
}

Acronym.makeNew = function() {
  Acronym.makeNew.counter = Acronym.makeNew.counter === undefined
      ? 0 : Acronym.makeNew.counter + 1;

    return new Acronym(
      'new-' + Acronym.makeNew.counter,
      '',
      '',
      '',
      '',
      '');
}

var AcronymChanges = Class.create({
  initialize: function(destElement, formId) {
    this.destElement = destElement;
    this.formId = formId;
    this.original = $H();
    this.changes = $H();
  },
  setOriginalData: function(data) {
    this.original = data.inject($H(), function(hash, value) {
      hash.set(value.id, value.clone());
      return hash;
    });
  },
  isChanged: function(acronym) {
    var orig = this.original.get(acronym.id);
    if (orig === undefined)
      return true;
    return !acronym.isEqual(orig);
  },
  revert: function(acronym) {
    var orig = this.original.get(acronym.id);
    if (orig === undefined)
      acronym.set(acronym.id, acronym.context, '', '', '', '');
    else
      acronym.setFrom(orig);
  },
  check: function(acronym) {
    if (acronym !== null) {
      if (acronym.isChanged())
        this.changes.set(acronym.id, acronym);
      else
        this.changes.unset(acronym.id);
    }
    this.publish();
  },
  publish: function() {
    this.destElement.value = Object.toJSON(this.changes.toObject());
    if (this.changes.size())
      on_change(this.formId);
    else
      on_reset(this.formId);
  },
  remove: function(id) {
    this.changes.unset(id);
  }
});

