import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HRComponent } from './hr.component';

describe('HRComponent', () => {
  let component: HRComponent;
  let fixture: ComponentFixture<HRComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HRComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HRComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
