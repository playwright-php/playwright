const { randomUUID } = require('node:crypto');

class NativeFrames {
  static #pages = new WeakMap();

  static bind(frame, selector) {
    const page = frame.page();

    if (page.isClosed() || frame.isDetached()) throw new Error('Frame has been detached.');

    let references = this.#pages.get(page);

    if (!references) {
      references = new Map();
      this.#pages.set(page, references);
      page.once('close', () => references.clear());
      page.on('framedetached', detached => {
        references.delete(references.get(detached));
        references.delete(detached);
      });
    }

    let id = references.get(frame);

    if (!id) {
      id = randomUUID();
      references.set(frame, id);
      references.set(id, { frame, selector: `${selector} >> css=:scope/*pw-frame:${id}*/` });
    }

    return references.get(id).selector;
  }

  static detached(page, selector) {
    const reference = this.#reference(selector);

    if (!reference) return null;

    const record = this.#pages.get(page)?.get(reference.id);

    if (!record) return true;

    if (!String(selector).startsWith(record.selector)) return null;

    return record.frame.isDetached();
  }

  static resolve(page, selector) {
    const reference = this.#reference(selector);

    if (!reference) return null;

    const record = this.#pages.get(page)?.get(reference.id);

    if (!record) throw new Error('Frame reference has expired.');

    if (!String(selector).startsWith(record.selector)) return null;

    return { frame: record.frame, remainder: String(selector).slice(record.selector.length) };
  }

  /** Reference-shaped text in CSS literals and comments must remain data. */
  static #reference(selector) {
    const tokens = String(selector).matchAll(/\\[\s\S]|"(?:\\(?:[\s\S]|$)|[^"\\])*(?:"|$)|'(?:\\(?:[\s\S]|$)|[^'\\])*(?:'|$)|\/\*[\s\S]*?(?:\*\/|$)|(?:^| >> )css=:scope\/\*pw-frame:([\s\S]*?)(?:\*\/|$)/g);

    for (const token of tokens) {
      if (token[1] !== undefined) return { id: token[1] };
    }

    return null;
  }
}

module.exports = { NativeFrames };
