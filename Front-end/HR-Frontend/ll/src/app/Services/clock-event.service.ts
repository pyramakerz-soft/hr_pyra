import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ClockEventService {
  private clockedInSource = new Subject<void>();

  clockedIn$ = this.clockedInSource.asObservable();

  notifyClockedIn() {
    this.clockedInSource.next();
  }
}