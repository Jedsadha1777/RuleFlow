export class RuleFlowException extends Error {
  constructor(message: string) {
    super(message);
    this.name = 'RuleFlowException';
  }
}