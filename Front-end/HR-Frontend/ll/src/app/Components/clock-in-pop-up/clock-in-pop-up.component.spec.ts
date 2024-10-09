import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClockInPopUpComponent } from './clock-in-pop-up.component';

describe('ClockInPopUpComponent', () => {
  let component: ClockInPopUpComponent;
  let fixture: ComponentFixture<ClockInPopUpComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ClockInPopUpComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClockInPopUpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
